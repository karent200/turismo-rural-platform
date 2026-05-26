from django.contrib.auth.models import AbstractUser
from django.db import models


class User(AbstractUser):
    ROLE_ADMIN = "admin"
    ROLE_PROVIDER = "prestador"
    ROLE_TOURIST = "turista"

    ROLE_CHOICES = (
        (ROLE_ADMIN, "Admin"),
        (ROLE_PROVIDER, "Prestador"),
        (ROLE_TOURIST, "Turista"),
    )

    role = models.CharField(max_length=20, choices=ROLE_CHOICES, default=ROLE_TOURIST)

    def __str__(self):
        return f"{self.username} ({self.role})"


class Service(models.Model):
    TYPE_ACCOMMODATION = "alojamiento"
    TYPE_RECREATION = "recreacion"
    TYPE_GASTRONOMY = "gastronomia"
    TYPE_ACTIVITIES = "actividades"

    TYPE_CHOICES = (
        (TYPE_ACCOMMODATION, "Alojamiento"),
        (TYPE_RECREATION, "Recreacion"),
        (TYPE_GASTRONOMY, "Gastronomia"),
        (TYPE_ACTIVITIES, "Actividades"),
    )

    provider = models.ForeignKey(User, on_delete=models.CASCADE, related_name="services")
    name = models.CharField(max_length=255)
    type = models.CharField(max_length=30, choices=TYPE_CHOICES)
    description = models.TextField(blank=True)
    capacity = models.PositiveIntegerField(default=1)
    price = models.DecimalField(max_digits=10, decimal_places=2)
    location = models.CharField(max_length=255, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ["-created_at"]

    def __str__(self):
        return self.name


class Availability(models.Model):
    service = models.ForeignKey(Service, on_delete=models.CASCADE, related_name="availabilities")
    date = models.DateField()
    slots_available = models.PositiveIntegerField(default=10)

    class Meta:
        unique_together = ("service", "date")
        ordering = ["date"]

    def __str__(self):
        return f"{self.service.name} - {self.date}"


class Reservation(models.Model):
    STATUS_PENDING = "pendiente"
    STATUS_CONFIRMED = "confirmada"
    STATUS_COMPLETED = "completada"
    STATUS_CANCELLED = "cancelada"

    STATUS_CHOICES = (
        (STATUS_PENDING, "Pendiente"),
        (STATUS_CONFIRMED, "Confirmada"),
        (STATUS_COMPLETED, "Completada"),
        (STATUS_CANCELLED, "Cancelada"),
    )

    tourist = models.ForeignKey(User, on_delete=models.CASCADE, related_name="reservations")
    service = models.ForeignKey(Service, on_delete=models.CASCADE, related_name="reservations")
    reservation_date = models.DateField()
    personas = models.PositiveIntegerField(default=1)
    telefono = models.CharField(max_length=20, blank=True)
    status = models.CharField(max_length=20, choices=STATUS_CHOICES, default=STATUS_PENDING)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ["-created_at"]

    def __str__(self):
        return f"{self.tourist.username} - {self.service.name} ({self.status})"


class Review(models.Model):
    service = models.ForeignKey(Service, on_delete=models.CASCADE, related_name="reviews")
    tourist = models.ForeignKey(User, on_delete=models.CASCADE, related_name="reviews")
    reservation = models.OneToOneField(Reservation, on_delete=models.CASCADE, related_name="review")
    rating = models.PositiveSmallIntegerField()
    comment = models.TextField(blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ["-created_at"]

    def __str__(self):
        return f"{self.tourist.username} - {self.service.name} ({self.rating})"
