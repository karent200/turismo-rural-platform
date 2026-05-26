from django.contrib.auth import get_user_model
from rest_framework import generics, permissions, status
from rest_framework.response import Response
from rest_framework_simplejwt.tokens import RefreshToken
from rest_framework.views import APIView

from .models import Reservation, Review, Service
from .permissions import IsAdmin, IsProvider, IsTourist
from django.db.models.functions import Lower
from .serializers import (
    RegisterSerializer,
    ReservationSerializer,
    ReviewSerializer,
    ServiceSerializer,
    UserSerializer,
)


class EmailLoginView(APIView):
    permission_classes = [permissions.AllowAny]

    def post(self, request):
        email = (request.data.get("email") or "").strip().lower()
        password = request.data.get("password") or ""
        if not email or not password:
            return Response({"detail": "Email y contrasena son requeridos."}, status=status.HTTP_400_BAD_REQUEST)

        user = get_user_model().objects.filter(email=email).first()
        if user is None or not user.check_password(password):
            return Response({"detail": "Credenciales invalidas."}, status=status.HTTP_401_UNAUTHORIZED)

        refresh = RefreshToken.for_user(user)
        return Response({"refresh": str(refresh), "access": str(refresh.access_token)})


class RegisterView(generics.CreateAPIView):
    serializer_class = RegisterSerializer
    permission_classes = [permissions.AllowAny]


class MeView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def get(self, request):
        serializer = UserSerializer(request.user)
        return Response(serializer.data)


class ServiceListCreateView(APIView):
    permission_classes = [permissions.AllowAny]

    def get(self, request):
        queryset = Service.objects.select_related("provider").all()
        service_type = request.query_params.get("type")
        if service_type:
            queryset = queryset.filter(type=service_type)
        location = request.query_params.get("location")
        if location:
            queryset = queryset.filter(location__icontains=location)
        serializer = ServiceSerializer(queryset, many=True)
        return Response(serializer.data)

    def post(self, request):
        if not IsProvider().has_permission(request, self):
            return Response({"detail": "No autorizado."}, status=status.HTTP_403_FORBIDDEN)
        serializer = ServiceSerializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        service = serializer.save(provider=request.user)
        return Response(ServiceSerializer(service).data, status=status.HTTP_201_CREATED)


class LocationListView(APIView):
    permission_classes = [permissions.AllowAny]

    def get(self, request):
        locs = (
            Service.objects
            .filter(location__isnull=False)
            .exclude(location__exact="")
            .values_list("location", flat=True)
            .distinct()
            .order_by(Lower("location"))
        )
        return Response([{"location": loc} for loc in locs])


class MyServicesView(APIView):
    permission_classes = [permissions.IsAuthenticated, IsProvider]

    def get(self, request):
        queryset = Service.objects.select_related("provider").filter(provider=request.user)
        serializer = ServiceSerializer(queryset, many=True)
        return Response(serializer.data)


class ReservationListCreateView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def get(self, request):
        queryset = Reservation.objects.select_related("service", "tourist")
        status_filter = request.query_params.get("status")
        if request.user.role == "turista":
            queryset = queryset.filter(tourist=request.user)
        elif request.user.role == "prestador":
            queryset = queryset.filter(service__provider=request.user)
        if status_filter:
            queryset = queryset.filter(status=status_filter)
        serializer = ReservationSerializer(queryset, many=True)
        return Response(serializer.data)

    def post(self, request):
        if not IsTourist().has_permission(request, self):
            return Response({"detail": "Solo turistas pueden reservar."}, status=status.HTTP_403_FORBIDDEN)
        serializer = ReservationSerializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        reservation = serializer.save(tourist=request.user)
        return Response(ReservationSerializer(reservation).data, status=status.HTTP_201_CREATED)


class ReservationStatusUpdateView(APIView):
    permission_classes = [permissions.IsAuthenticated, IsProvider]

    def patch(self, request, pk):
        try:
            reservation = Reservation.objects.select_related("service").get(pk=pk)
        except Reservation.DoesNotExist:
            return Response({"detail": "Reserva no encontrada."}, status=status.HTTP_404_NOT_FOUND)

        if request.user.role == "prestador" and reservation.service.provider_id != request.user.id:
            return Response({"detail": "No autorizado para esta reserva."}, status=status.HTTP_403_FORBIDDEN)

        serializer = ReservationSerializer(reservation, data=request.data, partial=True)
        serializer.is_valid(raise_exception=True)
        serializer.save()
        return Response(serializer.data)


class ReviewCreateView(APIView):
    permission_classes = [permissions.IsAuthenticated, IsTourist]

    def post(self, request):
        serializer = ReviewSerializer(data=request.data)
        serializer.is_valid(raise_exception=True)
        service_id = serializer.validated_data["service"].id if isinstance(serializer.validated_data["service"], Service) else serializer.validated_data["service"]
        reservation_id = serializer.validated_data["reservation"].id if isinstance(serializer.validated_data["reservation"], Reservation) else serializer.validated_data["reservation"]
        try:
            reservation = Reservation.objects.get(
                id=reservation_id,
                tourist=request.user,
                service_id=service_id,
                status="completada",
            )
        except Reservation.DoesNotExist:
            return Response(
                {"detail": "Reserva completada no encontrada para este servicio."},
                status=status.HTTP_400_BAD_REQUEST,
            )
        if hasattr(reservation, "review") and reservation.review is not None:
            return Response({"detail": "Ya has calificado esta reserva."}, status=status.HTTP_400_BAD_REQUEST)
        review = serializer.save(tourist=request.user, reservation=reservation, service_id=reservation.service_id)
        return Response(ReviewSerializer(review).data, status=status.HTTP_201_CREATED)


class ServiceReviewsView(APIView):
    permission_classes = [permissions.AllowAny]

    def get(self, request, service_id):
        reviews = Review.objects.filter(service_id=service_id).select_related("tourist", "service")
        serializer = ReviewSerializer(reviews, many=True)
        return Response(serializer.data)


class MyReviewsView(APIView):
    permission_classes = [permissions.IsAuthenticated, IsTourist]

    def get(self, request):
        reviews = Review.objects.filter(tourist=request.user).select_related("service", "tourist")
        serializer = ReviewSerializer(reviews, many=True)
        return Response(serializer.data)


class ProviderReviewsView(APIView):
    permission_classes = [permissions.IsAuthenticated, IsProvider]

    def get(self, request):
        reviews = Review.objects.filter(service__provider=request.user).select_related("tourist", "service")
        serializer = ReviewSerializer(reviews, many=True)
        return Response(serializer.data)


class AllReviewsView(APIView):
    permission_classes = [permissions.IsAuthenticated, IsAdmin]

    def get(self, request):
        reviews = Review.objects.all().select_related("tourist", "service")
        serializer = ReviewSerializer(reviews, many=True)
        return Response(serializer.data)


class ReviewDeleteView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def delete(self, request, pk):
        try:
            review = Review.objects.select_related("service").get(pk=pk)
        except Review.DoesNotExist:
            return Response({"detail": "Resena no encontrada."}, status=status.HTTP_404_NOT_FOUND)
        if request.user.role != "admin" and review.tourist != request.user:
            return Response({"detail": "No autorizado."}, status=status.HTTP_403_FORBIDDEN)
        review.delete()
        return Response(status=status.HTTP_204_NO_CONTENT)
