from django.contrib.auth import get_user_model
from rest_framework import serializers
from rest_framework_simplejwt.serializers import TokenObtainPairSerializer

from django.db.models import Avg, Count

from .models import Reservation, Review, Service

User = get_user_model()


class RegisterSerializer(serializers.ModelSerializer):
    password = serializers.CharField(write_only=True, min_length=6)
    username = serializers.CharField(required=False, allow_blank=True)

    class Meta:
        model = User
        fields = ("id", "username", "first_name", "last_name", "email", "role", "password")
        read_only_fields = ("id",)

    def validate(self, attrs):
        email = attrs.get("email", "").strip().lower()
        attrs["email"] = email
        if User.objects.filter(email=email).exists():
            raise serializers.ValidationError({"email": "Este email ya esta registrado."})
        return attrs

    def create(self, validated_data):
        password = validated_data.pop("password")
        username = (validated_data.get("username") or "").strip()
        if not username:
            email_prefix = validated_data["email"].split("@")[0]
            base_username = email_prefix or "user"
            username = base_username
            counter = 1
            while User.objects.filter(username=username).exists():
                username = f"{base_username}{counter}"
                counter += 1
            validated_data["username"] = username
        user = User(**validated_data)
        user.set_password(password)
        user.save()
        return user


class UserSerializer(serializers.ModelSerializer):
    class Meta:
        model = User
        fields = ("id", "username", "first_name", "last_name", "email", "role")


class ServiceSerializer(serializers.ModelSerializer):
    provider_name = serializers.CharField(source="provider.username", read_only=True)
    avg_rating = serializers.SerializerMethodField()
    total_reviews = serializers.SerializerMethodField()

    class Meta:
        model = Service
        fields = (
            "id",
            "provider",
            "provider_name",
            "name",
            "type",
            "description",
            "capacity",
            "price",
            "location",
            "created_at",
            "avg_rating",
            "total_reviews",
        )
        read_only_fields = ("id", "provider", "provider_name", "created_at", "avg_rating", "total_reviews")

    def get_avg_rating(self, obj):
        agg = obj.reviews.aggregate(avg=Avg("rating"))
        return round(agg["avg"], 1) if agg["avg"] is not None else None

    def get_total_reviews(self, obj):
        return obj.reviews.count()


class ReservationSerializer(serializers.ModelSerializer):
    tourist_name = serializers.CharField(source="tourist.username", read_only=True)
    service_name = serializers.CharField(source="service.name", read_only=True)
    has_review = serializers.SerializerMethodField()

    class Meta:
        model = Reservation
        fields = (
            "id",
            "tourist",
            "tourist_name",
            "service",
            "service_name",
            "reservation_date",
            "personas",
            "telefono",
            "status",
            "created_at",
            "has_review",
        )
        read_only_fields = ("id", "tourist", "tourist_name", "service_name", "created_at", "has_review")

    def get_has_review(self, obj):
        return hasattr(obj, "review") and obj.review is not None


class ReviewSerializer(serializers.ModelSerializer):
    tourist_name = serializers.CharField(source="tourist.username", read_only=True)
    service_name = serializers.CharField(source="service.name", read_only=True)

    class Meta:
        model = Review
        fields = (
            "id",
            "service",
            "service_name",
            "tourist",
            "tourist_name",
            "reservation",
            "rating",
            "comment",
            "created_at",
        )
        read_only_fields = ("id", "tourist", "tourist_name", "service_name", "created_at")

    def validate_rating(self, value):
        if value < 1 or value > 5:
            raise serializers.ValidationError("La calificacion debe estar entre 1 y 5.")
        return value


class EmailTokenObtainPairSerializer(TokenObtainPairSerializer):
    username_field = "email"

    def validate(self, attrs):
        email = attrs.get("email", "").strip().lower()
        password = attrs.get("password")
        if not email or not password:
            raise serializers.ValidationError({"detail": "Email y contrasena son requeridos."})

        try:
            user = User.objects.get(email=email)
        except User.DoesNotExist as exc:
            raise serializers.ValidationError({"detail": "Credenciales invalidas."}) from exc

        attrs["username"] = user.username
        return super().validate(attrs)
