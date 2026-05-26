from django.contrib import admin
from django.urls import include, path
from rest_framework_simplejwt.views import TokenRefreshView

from core.views import EmailLoginView

urlpatterns = [
    path("admin/", admin.site.urls),
    path("api/auth/login/", EmailLoginView.as_view(), name="token_obtain_pair"),
    path("api/auth/refresh/", TokenRefreshView.as_view(), name="token_refresh"),
    path("api/", include("core.urls")),
]
