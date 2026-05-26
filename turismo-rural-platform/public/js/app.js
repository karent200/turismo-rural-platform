const API_BASE = (typeof window !== "undefined" ? window.location.origin : "") + "/src/controllers/";

let currentUser = null;
let isLoggedIn = false;
let servicesList = [];
let currentServiceId = null;
let csrfToken = '';

document.addEventListener("DOMContentLoaded", function () {
    setupFilters();
    bindForms();
    checkSession();
    if (document.getElementById("servicesList")) {
        loadServices();
    }
});

async function apiRequest(path, options = {}) {
    const url = API_BASE + path;
    const method = options.method || "GET";
    const headers = { "Content-Type": "application/x-www-form-urlencoded" };

    let body = null;
    if (options.body) {
        if (typeof options.body === "string") {
            body = options.body;
        } else if (options.body instanceof FormData) {
            const params = new URLSearchParams();
            for (let [key, value] of options.body.entries()) {
                params.append(key, value);
            }
            body = params.toString();
        } else {
            body = new URLSearchParams(options.body).toString();
        }
        body += "&_csrf=" + encodeURIComponent(csrfToken);
    }

    try {
        const response = await fetch(url, {
            method,
            headers,
            credentials: "include",
            body
        });
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch {
            return { error: "Respuesta inválida del servidor" };
        }
    } catch (error) {
        return { error: error.message };
    }
}

function openWindow(windowId) {
    const modal = document.getElementById(`window-${windowId}`);
    if (!modal) return;

    modal.classList.add("active");
    const bg = document.querySelector(".bg-layer");
    const hub = document.getElementById("mainHub");
    if (bg) bg.style.filter = "blur(8px)";
    if (hub) {
        hub.style.opacity = "0.2";
        hub.style.pointerEvents = "none";
    }

    if (windowId === "services") loadServices();
    if (windowId === "reservations") {
        if (isLoggedIn) loadReservations();
        else {
            closeWindow("reservations");
            openWindow("auth");
        }
    }
}

function closeWindow(windowId) {
    const modal = document.getElementById(`window-${windowId}`);
    if (!modal) return;

    modal.classList.remove("active");
    const bg = document.querySelector(".bg-layer");
    const hub = document.getElementById("mainHub");
    if (bg) bg.style.filter = "blur(2px)";
    if (hub) {
        hub.style.opacity = "1";
        hub.style.pointerEvents = "all";
    }
}

window.onclick = function (event) {
    if (!event.target.classList.contains("window-overlay")) return;
    const activeModal = document.querySelector(".window-overlay.active");
    if (!activeModal) return;
    closeWindow(activeModal.id.replace("window-", ""));
};

async function loadServices() {
    const container = document.getElementById("servicesList");
    if (!container) return;
    container.innerHTML = '<p class="text-muted" style="padding: 2rem; text-align: center;">Cargando servicios...</p>';

    const typeFilter = document.getElementById("typeFilter");
    const locationFilter = document.getElementById("locationFilter");
    const type = typeFilter ? typeFilter.value : "";
    const location = locationFilter ? locationFilter.value.trim() : "";

    let query = "services.php?action=list";
    if (type) query += "&type=" + encodeURIComponent(type);
    if (location) query += "&location=" + encodeURIComponent(location);

    const data = await apiRequest(query);
    if (data.error) {
        container.innerHTML = '<p class="text-muted">Error: ' + data.error + "</p>";
        return;
    }
    renderServices(Array.isArray(data) ? data : []);
}

function renderServices(services) {
    const container = document.getElementById("servicesList");
    if (!container) return;

    if (!services.length) {
        container.innerHTML = '<p class="text-muted" style="padding: 2rem; text-align: center;">No hay servicios disponibles.</p>';
        return;
    }

    servicesList = services;
    container.innerHTML = services.map(function (s, index) {
        const btnAction = isLoggedIn ? "reservar(" + s.id + ", " + index + ")" : "openWindow('auth')";
        return (
            '<div class="service-card">' +
            '<span class="type-badge"><i class="fa-solid fa-tag"></i> ' + (s.type || "Sin tipo") + "</span>" +
            "<h5>" + (s.name || "Sin nombre") + "</h5>" +
            '<p class="description">' + (s.description || "Sin descripción") + "</p>" +
            '<p class="location"><i class="fa-solid fa-location-dot"></i> ' + (s.location || "Sin ubicación") + "</p>" +
            '<p class="service-price">$' + parseInt(s.price || 0).toLocaleString() + " COP</p>" +
            '<button class="btn-reservar" onclick="' + btnAction + '">' +
            (isLoggedIn ? "Reservar" : "Inicia Sesión") +
            "</button></div>"
        );
    }).join("");
}

function setupFilters() {
    const typeFilter = document.getElementById("typeFilter");
    const locationFilter = document.getElementById("locationFilter");
    const filterBtn = document.getElementById("filterBtn");
    const run = () => loadServices();
    if (filterBtn) filterBtn.onclick = run;
    if (typeFilter) typeFilter.onchange = run;
}

async function reservar(serviceId, serviceIndex) {
    if (!isLoggedIn) {
        openWindow("auth");
        return;
    }
    currentServiceId = serviceId;
    const serviceName = servicesList[serviceIndex]?.name || "Servicio";
    const el = document.getElementById("reserva-servicio");
    if (el) el.value = serviceName;
    openWindow("nueva-reserva");
}

function bindForms() {
    const authForm = document.getElementById("authForm");
    if (authForm) authForm.addEventListener("submit", handleAuth);
    const registerForm = document.getElementById("registerForm");
    if (registerForm) registerForm.addEventListener("submit", handleRegister);
}

async function handleAuth(e) {
    e.preventDefault();
    const email = (document.getElementById("email")?.value || "").trim().toLowerCase();
    const password = document.getElementById("password")?.value || "";
    const data = await apiRequest("auth.php", {
        method: "POST",
        body: "action=login&email=" + encodeURIComponent(email) + "&password=" + encodeURIComponent(password)
    });
    if (data.success) {
        currentUser = data.user;
        isLoggedIn = true;
        localStorage.setItem("turismo_user", JSON.stringify(currentUser));
        closeWindow("auth");
        loadServices();
    } else {
        alert(data.error || "Error en autenticación");
    }
}

async function handleRegister(e) {
    e.preventDefault();
    const name = (document.getElementById("regName")?.value || "").trim();
    const email = (document.getElementById("regEmail")?.value || "").trim().toLowerCase();
    const password = document.getElementById("regPassword")?.value || "";
    const role = document.getElementById("regRole")?.value || "turista";

    if (password.length < 8) { alert("Mínimo 8 caracteres"); return; }
    if (!/[A-Z]/.test(password)) { alert("Falta una mayúscula"); return; }
    if (!/[0-9]/.test(password)) { alert("Falta un número"); return; }
    if (!/[^a-zA-Z0-9]/.test(password)) { alert("Falta un carácter especial"); return; }

    const data = await apiRequest("auth.php", {
        method: "POST",
        body:
            "action=register&name=" +
            encodeURIComponent(name) +
            "&email=" +
            encodeURIComponent(email) +
            "&password=" +
            encodeURIComponent(password) +
            "&role=" +
            encodeURIComponent(role)
    });
    if (data.success) alert("Registro exitoso. Inicia sesión.");
    else alert(data.error || "No se pudo registrar");
}

async function loadReservations() {
    const container = document.getElementById("reservationsList");
    if (!container) return;
    const data = await apiRequest("reservations.php?action=my_reservations");
    if (!Array.isArray(data) || !data.length) {
        container.innerHTML = '<p class="text-muted">No tienes reservas aún.</p>';
        return;
    }
    container.innerHTML = data
        .map(
            (r) =>
                '<div class="service-card"><h5>' +
                r.service_name +
                "</h5><p>" +
                r.reservation_date +
                " — " +
                r.status +
                "</p></div>"
        )
        .join("");
}

function checkSession() {
    const stored = localStorage.getItem("turismo_user");
    if (stored) {
        try {
            currentUser = JSON.parse(stored);
            isLoggedIn = true;
        } catch {
            currentUser = null;
            isLoggedIn = false;
        }
    }
    fetch(API_BASE + "auth.php", { credentials: "include" })
        .then(r => r.json())
        .then(d => { csrfToken = d.csrf_token || ''; })
        .catch(() => {});
}

function logout() {
    localStorage.removeItem("turismo_user");
    currentUser = null;
    isLoggedIn = false;
    apiRequest("auth.php", { method: "POST", body: "action=logout" });
}

window.openWindow = openWindow;
window.closeWindow = closeWindow;
window.loadServices = loadServices;
window.handleAuth = handleAuth;
window.logout = logout;
