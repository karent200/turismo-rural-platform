from django.urls import path

from .views import (
    AllReviewsView,
    LocationListView,
    MeView,
    MyReviewsView,
    MyServicesView,
    ProviderReviewsView,
    RegisterView,
    ReservationListCreateView,
    ReservationStatusUpdateView,
    ReviewCreateView,
    ReviewDeleteView,
    ServiceListCreateView,
    ServiceReviewsView,
)

urlpatterns = [
    path('auth/register/', RegisterView.as_view()),
    path('auth/me/', MeView.as_view()),
    path('services/', ServiceListCreateView.as_view()),
    path('services/my/', MyServicesView.as_view()),
    path('services/locations/', LocationListView.as_view()),
    path('services/<int:service_id>/reviews/', ServiceReviewsView.as_view()),
    path('reservations/', ReservationListCreateView.as_view()),
    path('reservations/<int:pk>/status/', ReservationStatusUpdateView.as_view()),
    path('reviews/', ReviewCreateView.as_view()),
    path('reviews/my/', MyReviewsView.as_view()),
    path('reviews/provider/', ProviderReviewsView.as_view()),
    path('reviews/all/', AllReviewsView.as_view()),
    path('reviews/<int:pk>/', ReviewDeleteView.as_view()),
]
