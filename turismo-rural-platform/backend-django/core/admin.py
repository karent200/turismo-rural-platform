from django.contrib import admin
from django.contrib.auth.admin import UserAdmin as DjangoUserAdmin

from .models import Availability, Reservation, Service, User

admin.site.register(User, DjangoUserAdmin)
admin.site.register(Service)
admin.site.register(Availability)
admin.site.register(Reservation)
