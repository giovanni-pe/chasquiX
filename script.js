
        // Configuración global
        const MQTT_CONFIG = {
            host: 'localhost',
            port: 9001,
            topics: {
                clients: 'chasquix/clients',
                drivers: 'chasquix/drivers',
                routes: 'chasquix/routes',
                messages: 'chasquix/messages',
                assignments: 'chasquix/assignments'
            }
        };

        // Variables globales
        let map;
        let mqttClient;
        let currentUser = {
            id: 'user_' + Date.now(),
            type: 'client',
            lat: null,
            lng: null,
            status: 'waiting'
        };
        let markers = new Map();
        let routeControl = null;
        let currentRequest = null;
        let destinationMarker = null;
        let destinationCoords = null;
        let selectingDestination = false;
        let activeAssignment = null;
        let chatMessages = [];
        let chatPartner = null;
        let unreadMessages = 0;

        // Inicializar la aplicación
        document.addEventListener('DOMContentLoaded', function () {
            initMap();
            initMQTT();
            initEventListeners();
            getUserLocation();
            updateUI();
        });

        // Inicializar el mapa
        function initMap() {
            // Coordenadas de Tingo María, Perú
            const tingoMaria = [-9.2945, -76.0073];

            map = L.map('map').setView(tingoMaria, 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Evento para hacer clic en el mapa
            map.on('click', function (e) {
                if (selectingDestination) {
                    setDestination(e.latlng.lat, e.latlng.lng);
                }
            });
        }

        // Inicializar MQTT
        function initMQTT() {
            mqttClient = new Paho.MQTT.Client(MQTT_CONFIG.host, MQTT_CONFIG.port, currentUser.id);

            mqttClient.onConnectionLost = function (response) {
                console.log('Conexión MQTT perdida:', response);
                updateConnectionStatus(false);
                setTimeout(initMQTT, 5000); // Reintentar conexión
            };

            mqttClient.onMessageArrived = function (message) {
                handleMQTTMessage(message);
            };

            // Conectar al broker MQTT
            mqttClient.connect({
                onSuccess: function () {
                    console.log('Conectado a MQTT');
                    updateConnectionStatus(true);
                    subscribeToTopics();
                },
                onFailure: function (error) {
                    console.error('Error conectando a MQTT:', error);
                    updateConnectionStatus(false);
                    setTimeout(initMQTT, 5000); // Reintentar
                }
            });
        }

        // Suscribirse a los topics MQTT
        function subscribeToTopics() {
            Object.values(MQTT_CONFIG.topics).forEach(topic => {
                mqttClient.subscribe(topic);
                console.log('Suscrito a:', topic);
            });
        }

        // Manejar mensajes MQTT
        function handleMQTTMessage(message) {
            try {
                const data = JSON.parse(message.payloadString);
                const topic = message.destinationName;

                console.log('Mensaje recibido:', topic, data);

                switch (topic) {
                    case MQTT_CONFIG.topics.clients:
                        handleClientMessage(data);
                        break;
                    case MQTT_CONFIG.topics.drivers:
                        handleDriverMessage(data);
                        break;
                    case MQTT_CONFIG.topics.routes:
                        handleRouteMessage(data);
                        break;
                    case MQTT_CONFIG.topics.messages:
                        handleDirectMessage(data);
                        break;
                    case MQTT_CONFIG.topics.assignments:
                        handleAssignmentMessage(data);
                        break;
                }
            } catch (error) {
                console.error('Error procesando mensaje MQTT:', error);
            }
        }

        // Manejar mensajes de clientes
        function handleClientMessage(data) {
            if (data.action === 'request') {
                if (data.userId !== currentUser.id) {
                    addOrUpdateMarker(data, 'client');
                    addToRequestsList(data, 'client');
                }
            } else if (data.action === 'cancel') {
                removeMarker(data.userId);
                removeFromRequestsList(data.userId);
            }
        }

        // Manejar mensajes de conductores con solicitudes de servicio
        function handleDriverMessage(data) {
            if (data.action === 'available') {
                if (data.userId !== currentUser.id) {
                    addOrUpdateMarker(data, 'driver');
                    addToRequestsList(data, 'driver');
                }
            } else if (data.action === 'accept') {
                handleDriverAcceptance(data);
            } else if (data.action === 'request_client') {
                // Conductor solicita a cliente
                if (data.targetClientId === currentUser.id) {
                    handleDriverRequestToClient(data);
                }
            } else if (data.action === 'offline') {
                removeMarker(data.userId);
                removeFromRequestsList(data.userId);
            }

            // Mostrar lista de clientes si es taxista
            if (currentUser.type === 'driver' && currentUser.status === 'available') {
                updateClientList();
            }
        }

        // Nueva función para actualizar lista de clientes
        function updateClientList() {
            const requestsList = document.getElementById('requestsList');
            requestsList.innerHTML = '<h3>👥 Clientes Solicitando Taxi</h3>';

            let hasClients = false;

            markers.forEach((marker, userId) => {
                const userData = marker.options.userData;

                if (userData.type === 'client' && userData.action === 'request' && userId !== currentUser.id) {
                    hasClients = true;
                    addClientToList(userData, userId);
                }
            });

            if (!hasClients) {
                requestsList.innerHTML += '<p>No hay clientes solicitando servicio en este momento</p>';
            }
        }

        // Nueva función para añadir cliente a la lista
        function addClientToList(clientData, clientId) {
            const requestsList = document.getElementById('requestsList');
            const distance = calculateDistance(
                currentUser.lat, currentUser.lng,
                clientData.lat, clientData.lng
            );

            const clientItem = document.createElement('div');
            clientItem.className = 'client-item';
            clientItem.dataset.clientId = clientId;

            clientItem.innerHTML = `
                <div class="client-header">
                    <h4>🙋‍♂️ Cliente ${clientId.substring(0, 6)}</h4>
                    <span class="distance-badge">${distance.toFixed(1)} km</span>
                </div>
                <p><strong>Ubicación:</strong> ${clientData.lat.toFixed(4)}, ${clientData.lng.toFixed(4)}</p>
                ${clientData.destination ? `<p><strong>Destino:</strong> ${clientData.destination}</p>` : ''}
                ${clientData.description ? `<p><strong>Notas:</strong> ${clientData.description}</p>` : ''}
                <div class="client-actions">
                    <button class="btn-accept" onclick="acceptClientRequest('${clientId}')">
                        🚗 Aceptar Viaje
                    </button>
                    <button class="btn-chat" onclick="initiateChatWithClient('${clientId}')">
                        💬 Chatear
                    </button>
                </div>
            `;

            requestsList.appendChild(clientItem);
        }

        // Nueva función para aceptar cliente
        window.acceptClientRequest = function (clientId) {
            const clientData = markers.get(clientId)?.options?.userData;

            if (!clientData) {
                showNotification('Cliente no encontrado', 'error');
                return;
            }

            const assignment = {
                clientId: clientId,
                driverId: currentUser.id,
                action: 'assigned',
                driverLocation: {
                    lat: currentUser.lat,
                    lng: currentUser.lng
                },
                clientLocation: {
                    lat: clientData.lat,
                    lng: clientData.lng
                },
                destination: clientData.destinationLat ? {
                    lat: clientData.destinationLat,
                    lng: clientData.destinationLng
                } : null,
                timestamp: Date.now()
            };

            publishMessage(MQTT_CONFIG.topics.assignments, assignment);
            showNotification(`Viaje aceptado con cliente ${clientId.substring(0, 6)}`, 'success');

            // Iniciar chat automáticamente
            initiateChatWithClient(clientId);
        };

        // Nueva función para iniciar chat con cliente
        window.initiateChatWithClient = function (clientId) {
            chatPartner = {
                id: clientId,
                type: 'client',
                name: `Cliente ${clientId.substring(0, 6)}`
            };

            updateChatButton();
            openChat();
            addSystemMessage(`Chat iniciado con cliente ${clientId.substring(0, 6)}`);

            // Notificar al cliente
            const chatNotification = {
                targetId: clientId,
                senderId: currentUser.id,
                message: `El mototaxista quiere comunicarse contigo`,
                type: 'chat_init',
                timestamp: Date.now()
            };

            publishMessage(MQTT_CONFIG.topics.messages, chatNotification);
        };

        // Manejar cuando un conductor solicita a un cliente
        function handleDriverRequestToClient(data) {
            showNotification(`Conductor ${data.userId} quiere ofrecerte servicio`, 'info');

            // Establecer chat con el conductor
            chatPartner = {
                id: data.userId,
                type: 'driver'
            };
            updateChatButton();

            // Incrementar mensajes no leídos
            unreadMessages++;
            updateUnreadIndicator();

            // Agregar mensaje al chat
            addSystemMessage(`El conductor ${data.userId} está interesado en tu viaje. Puedes chatear para acordar detalles.`);
        }

        // Manejar mensajes de asignación
        function handleAssignmentMessage(data) {
            if (data.clientId === currentUser.id || data.driverId === currentUser.id) {
                activeAssignment = data;

                if (data.action === 'assigned') {
                    handleAssignment(data);
                } else if (data.action === 'completed') {
                    handleTripCompletion(data);
                }
            }
        }

        // Manejar asignación de viaje
        function handleAssignment(data) {
            if (data.clientId === currentUser.id) {
                // Cliente: ocultar otros taxistas
                markers.forEach((marker, markerId) => {
                    if (markerId !== data.driverId && markerId !== currentUser.id) {
                        map.removeLayer(marker);
                    }
                });

                showNotification(`¡Taxista ${data.driverId} ha aceptado tu solicitud!`, 'success');
                currentUser.status = 'in_trip';

                // Establecer chat con el conductor
                chatPartner = {
                    id: data.driverId,
                    type: 'driver'
                };
                updateChatButton();
                addSystemMessage('Ahora puedes chatear con tu conductor');

                // Mostrar solo el taxista asignado
                if (data.driverLocation) {
                    addOrUpdateMarker({
                        userId: data.driverId,
                        lat: data.driverLocation.lat,
                        lng: data.driverLocation.lng,
                        status: 'assigned'
                    }, 'driver');
                }
            } else if (data.driverId === currentUser.id) {
                // Conductor: mostrar ruta al cliente
                showNotification(`¡Nueva carrera asignada!`, 'success');
                currentUser.status = 'in_trip';

                // Establecer chat con el cliente
                chatPartner = {
                    id: data.clientId,
                    type: 'client'
                };
                updateChatButton();
                addSystemMessage('Ahora puedes chatear con tu pasajero');

                if (data.clientLocation && data.destination) {
                    drawAssignedRoute(data.clientLocation, data.destination);
                }
            }

            updateUI();
        }

        // Inicializar event listeners
        function initEventListeners() {
            document.getElementById('userTypeSelect').addEventListener('change', function () {
                currentUser.type = this.value;
                updateUI();
            });

            document.getElementById('actionBtn').addEventListener('click', function () {
                if (currentUser.type === 'client') {
                    getUserLocation();
                } else {
                    sendDriverAvailability();
                }
            });

            document.getElementById('selectDestinationBtn').addEventListener('click', function () {
                startDestinationSelection();
            });

            document.getElementById('confirmRequestBtn').addEventListener('click', function () {
                confirmAndSendRequest();
            });

            document.getElementById('clearRouteBtn').addEventListener('click', function () {
                clearRoute();
            });

            document.getElementById('cancelBtn').addEventListener('click', function () {
                cancelCurrentRequest();
            });

            // Click en elementos de la lista
            document.getElementById('requestsList').addEventListener('click', function (e) {
                const requestItem = e.target.closest('.request-item');
                if (requestItem && e.target.classList.contains('accept-btn')) {
                    const data = JSON.parse(requestItem.dataset.requestData);
                    acceptRequest(data.userId, data);
                }
            });

            // Botón de chat
            document.getElementById('openChatBtn').addEventListener('click', function () {
                openChat();
            });
        }

        // Obtener ubicación del usuario
        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        updateLocation(position.coords.latitude, position.coords.longitude);

                        if (currentUser.type === 'client') {
                            showNotification('Ubicación actualizada. Ahora selecciona tu destino.', 'info');
                            document.getElementById('selectDestinationBtn').style.display = 'block';
                        }
                    },
                    function (error) {
                        console.error('Error obteniendo ubicación:', error);
                        // Usar ubicación por defecto en Tingo María
                        updateLocation(-9.2945, -76.0073);
                    }
                );
            } else {
                updateLocation(-9.2945, -76.0073);
            }
        }

        // Actualizar ubicación
        function updateLocation(lat, lng) {
            currentUser.lat = lat;
            currentUser.lng = lng;

            document.getElementById('lat').textContent = lat.toFixed(6);
            document.getElementById('lng').textContent = lng.toFixed(6);

            // Centrar mapa en la nueva ubicación
            map.setView([lat, lng], 16);

            // Agregar/actualizar marcador del usuario actual
            addOrUpdateMarker(currentUser, currentUser.type);
        }

        // Iniciar selección de destino
        function startDestinationSelection() {
            selectingDestination = true;
            document.getElementById('destinationSelector').classList.add('active');
            document.getElementById('destinationSelector').innerHTML =
                '<p>🎯 <strong>Haz click en el mapa para marcar tu destino</strong></p>';

            map.getContainer().style.cursor = 'crosshair';
            showNotification('Haz click en el mapa para seleccionar tu destino', 'info');
        }

        // Establecer destino
        function setDestination(lat, lng) {
            destinationCoords = { lat, lng };

            // Remover marcador anterior si existe
            if (destinationMarker) {
                map.removeLayer(destinationMarker);
            }

            // Crear marcador de destino
            const iconHtml = '<div class="marker-pin destination-marker"></div>';
            const customIcon = L.divIcon({
                html: iconHtml,
                className: 'custom-div-icon',
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            });

            destinationMarker = L.marker([lat, lng], { icon: customIcon }).addTo(map);
            destinationMarker.bindPopup('<strong>🎯 Tu destino</strong>').openPopup();

            // Dibujar ruta
            drawRoute();

            // Actualizar UI
            selectingDestination = false;
            map.getContainer().style.cursor = '';
            document.getElementById('destinationSelector').classList.remove('active');
            document.getElementById('destinationSelector').innerHTML =
                '<p>✅ <strong>Destino seleccionado</strong></p>';

            document.getElementById('confirmButtons').style.display = 'block';
            document.getElementById('selectDestinationBtn').style.display = 'none';
        }

        // Dibujar ruta
        function drawRoute() {
            // Remover ruta anterior si existe
            if (routeControl) {
                map.removeControl(routeControl);
            }

            routeControl = L.Routing.control({
                waypoints: [
                    L.latLng(currentUser.lat, currentUser.lng),
                    L.latLng(destinationCoords.lat, destinationCoords.lng)
                ],
                routeWhileDragging: false,
                addWaypoints: false,
                createMarker: function () { return null; }, // No crear marcadores adicionales
                lineOptions: {
                    styles: [{ color: '#764ba2', weight: 4, opacity: 0.7 }]
                }
            }).on('routesfound', function (e) {
                const routes = e.routes;
                const summary = routes[0].summary;

                // Actualizar información de ruta
                document.getElementById('routeInfo').style.display = 'block';
                document.getElementById('routeDistance').textContent =
                    (summary.totalDistance / 1000).toFixed(2) + ' km';
                document.getElementById('routeDuration').textContent =
                    Math.round(summary.totalTime / 60) + ' min';

                // Ajustar vista del mapa
                const bounds = L.latLngBounds([
                    [currentUser.lat, currentUser.lng],
                    [destinationCoords.lat, destinationCoords.lng]
                ]);
                map.fitBounds(bounds, { padding: [50, 50] });
            }).addTo(map);
        }

        // Confirmar y enviar solicitud
        function confirmAndSendRequest() {
            if (!currentUser.lat || !currentUser.lng || !destinationCoords) {
                alert('Por favor, selecciona tu ubicación y destino');
                return;
            }

            const destination = document.getElementById('destination').value.trim();
            const description = document.getElementById('description').value.trim();

            const request = {
                userId: currentUser.id,
                action: 'request',
                lat: currentUser.lat,
                lng: currentUser.lng,
                destinationLat: destinationCoords.lat,
                destinationLng: destinationCoords.lng,
                destination: destination || 'Destino seleccionado en el mapa',
                description: description,
                timestamp: Date.now(),
                status: 'waiting'
            };

            publishMessage(MQTT_CONFIG.topics.clients, request);
            currentRequest = request;
            currentUser.status = 'requesting';
            updateUI();

            showNotification('Solicitud de taxi enviada. Buscando conductores cercanos...', 'success');

            // Mostrar taxistas cercanos
            highlightNearbyDrivers();
        }

        // Resaltar conductores cercanos
        function highlightNearbyDrivers() {
            const maxDistance = 2; // km

            markers.forEach((marker, markerId) => {
                const markerData = marker.options.userData;
                if (markerData && markerData.type === 'driver') {
                    const distance = calculateDistance(
                        currentUser.lat, currentUser.lng,
                        markerData.lat, markerData.lng
                    );

                    if (distance <= maxDistance) {
                        marker.setIcon(createPulsingIcon('driver'));

                        // Notificar al conductor
                        const notification = {
                            targetId: markerId,
                            senderId: currentUser.id,
                            message: `Nueva solicitud de taxi cerca (${distance.toFixed(1)} km)`,
                            type: 'nearby_request',
                            data: currentRequest
                        };
                        publishMessage(MQTT_CONFIG.topics.messages, notification);
                    }
                }
            });
        }

        // Crear icono con efecto de pulso
        function createPulsingIcon(type) {
            const iconClass = type === 'client' ? 'client-marker' : 'driver-marker';
            const iconHtml = `<div class="marker-pin ${iconClass} pulse"></div>`;

            return L.divIcon({
                html: iconHtml,
                className: 'custom-div-icon',
                iconSize: [30, 30],
                iconAnchor: [15, 30]
            });
        }

        // Limpiar ruta
        function clearRoute() {
            if (routeControl) {
                map.removeControl(routeControl);
                routeControl = null;
            }

            if (destinationMarker) {
                map.removeLayer(destinationMarker);
                destinationMarker = null;
            }

            destinationCoords = null;
            document.getElementById('routeInfo').style.display = 'none';
            document.getElementById('confirmButtons').style.display = 'none';
            document.getElementById('selectDestinationBtn').style.display = 'block';
            document.getElementById('destinationSelector').innerHTML =
                '<p>📍 <strong>Click en el mapa para seleccionar destino</strong></p>';
        }

        // Enviar disponibilidad del conductor
        function sendDriverAvailability() {
            if (!currentUser.lat || !currentUser.lng) {
                alert('Por favor, permite el acceso a tu ubicación');
                return;
            }

            const description = document.getElementById('description').value.trim();
            const price = document.getElementById('price').value;

            const availability = {
                userId: currentUser.id,
                action: 'available',
                lat: currentUser.lat,
                lng: currentUser.lng,
                description: description || 'Mototaxi disponible',
                price: price || '10.00',
                timestamp: Date.now(),
                status: 'available'
            };

            publishMessage(MQTT_CONFIG.topics.drivers, availability);
            currentRequest = availability;
            currentUser.status = 'available';
            updateUI();

            showNotification('Ahora estás disponible para servicios', 'success');
        }

        // Cancelar solicitud actual
        function cancelCurrentRequest() {
            if (currentRequest) {
                const cancelMessage = {
                    userId: currentUser.id,
                    action: 'cancel',
                    timestamp: Date.now()
                };

                const topic = currentUser.type === 'client' ?
                    MQTT_CONFIG.topics.clients : MQTT_CONFIG.topics.drivers;

                publishMessage(topic, cancelMessage);

                currentRequest = null;
                currentUser.status = 'waiting';
                clearRoute();
                updateUI();

                showNotification('Solicitud cancelada', 'info');
            }
        }

        // Aceptar solicitud (para conductores)
        function acceptRequest(requestId, clientData) {
            // Si el conductor acepta a un cliente
            if (currentUser.type === 'driver' && clientData.action === 'request') {
                const acceptance = {
                    clientId: requestId,
                    driverId: currentUser.id,
                    action: 'assigned',
                    driverLocation: {
                        lat: currentUser.lat,
                        lng: currentUser.lng
                    },
                    clientLocation: {
                        lat: clientData.lat,
                        lng: clientData.lng
                    },
                    destination: clientData.destinationLat ? {
                        lat: clientData.destinationLat,
                        lng: clientData.destinationLng
                    } : null,
                    timestamp: Date.now()
                };

                publishMessage(MQTT_CONFIG.topics.assignments, acceptance);

                // Notificar al cliente
                const notification = {
                    targetId: requestId,
                    senderId: currentUser.id,
                    message: `Conductor ${currentUser.id} ha aceptado tu solicitud`,
                    type: 'acceptance'
                };
                publishMessage(MQTT_CONFIG.topics.messages, notification);

                currentUser.status = 'in_trip';
                updateUI();
                showNotification('Solicitud aceptada. Dirigiéndose al cliente...', 'success');
            }
            // Si el cliente solicita a un conductor específico
            else if (currentUser.type === 'client' && clientData.action === 'available') {
                requestServiceFromDriver(requestId, clientData);
            }
        }

        // Cliente solicita servicio a conductor específico
        function requestServiceFromDriver(driverId, driverData) {
            const destination = document.getElementById('destination').value.trim();

            // Si no hay destino seleccionado, pedir que lo seleccione
            if (!destinationCoords && !destination) {
                showNotification('Por favor, selecciona primero tu destino en el mapa', 'warning');
                startDestinationSelection();
                return;
            }

            const serviceRequest = {
                userId: currentUser.id,
                action: 'request',
                targetDriverId: driverId,
                lat: currentUser.lat,
                lng: currentUser.lng,
                destinationLat: destinationCoords?.lat,
                destinationLng: destinationCoords?.lng,
                destination: destination || 'Destino seleccionado en el mapa',
                description: document.getElementById('description').value.trim(),
                driverLat: driverData.lat,
                driverLng: driverData.lng,
                timestamp: Date.now()
            };

            // Establecer chat con el conductor
            chatPartner = {
                id: driverId,
                type: 'driver'
            };
            updateChatButton();

            // Enviar mensaje directo al conductor
            const directMessage = {
                targetId: driverId,
                senderId: currentUser.id,
                message: `Cliente te solicita servicio a: ${serviceRequest.destination}`,
                type: 'service_request',
                data: serviceRequest
            };

            publishMessage(MQTT_CONFIG.topics.messages, directMessage);

            currentUser.status = 'requesting';
            currentRequest = serviceRequest;
            updateUI();

            showNotification(`Solicitud enviada al conductor ${driverId}`, 'success');

            // Abrir chat automáticamente
            openChat();
            addSystemMessage(`Has solicitado servicio al conductor. Puedes chatear mientras esperas respuesta.`);
        }

        // Dibujar ruta asignada (para conductor)
        function drawAssignedRoute(clientLocation, destination) {
            if (routeControl) {
                map.removeControl(routeControl);
            }

            const waypoints = [
                L.latLng(currentUser.lat, currentUser.lng),
                L.latLng(clientLocation.lat, clientLocation.lng)
            ];

            if (destination) {
                waypoints.push(L.latLng(destination.lat, destination.lng));
            }

            routeControl = L.Routing.control({
                waypoints: waypoints,
                routeWhileDragging: false,
                addWaypoints: false,
                createMarker: function () { return null; },
                lineOptions: {
                    styles: [{ color: '#28a745', weight: 5, opacity: 0.7 }]
                }
            }).on('routesfound', function (e) {
                const bounds = L.latLngBounds(waypoints);
                map.fitBounds(bounds, { padding: [50, 50] });
            }).addTo(map);
        }

        // Publicar mensaje MQTT
        function publishMessage(topic, data) {
            if (mqttClient && mqttClient.isConnected()) {
                const message = new Paho.MQTT.Message(JSON.stringify(data));
                message.destinationName = topic;
                mqttClient.send(message);
                console.log('Mensaje enviado a', topic, data);
            } else {
                console.error('Cliente MQTT no conectado');
                showNotification('Error de conexión. Reintentando...', 'error');
            }
        }

        // Agregar o actualizar marcador en el mapa
        function addOrUpdateMarker(data, type) {
            const markerId = data.userId;

            // Remover marcador existente si existe
            if (markers.has(markerId)) {
                map.removeLayer(markers.get(markerId));
            }

            // Determinar clase CSS según el tipo
            let markerClass = '';
            if (markerId === currentUser.id) {
                markerClass = 'my-marker';
            } else if (type === 'client') {
                markerClass = 'client-marker';
            } else if (type === 'driver') {
                markerClass = 'driver-marker';
            }

            // Crear icono personalizado
            const iconHtml = `
        <div class="marker-icon ${markerClass}">
            <div class="orientation-arrow"></div>
        </div>
    `;

            const customIcon = L.divIcon({
                html: iconHtml,
                className: 'custom-div-icon',
                iconSize: [40, 55], // Tamaño mayor para incluir la flecha
                iconAnchor: [20, 55], // El ancla en la punta de la flecha
                popupAnchor: [0, -55] // Posición del popup arriba del marcador
            });

            // Crear marcador
            const marker = L.marker([data.lat, data.lng], {
                icon: customIcon,
                userData: { ...data, type }
            }).addTo(map);

            // Crear popup con información
            let popupContent = `
        <div style="min-width: 200px;">
            <h4>${type === 'client' ? '🙋‍♂️ Cliente' : '🏍️ Mototaxista'}</h4>
            <p><strong>ID:</strong> ${data && data.userId ? data.userId.substring(0, 8) : 'N/A'}</p>

            <p><strong>Hora:</strong> ${new Date(data.timestamp).toLocaleTimeString()}</p>
    `;

            if (type === 'client' && data.destination) {
                popupContent += `<p><strong>Destino:</strong> ${data.destination}</p>`;
            }

            if (data.description) {
                popupContent += `<p><strong>Descripción:</strong> ${data.description}</p>`;
            }

            if (type === 'driver' && data.price) {
                popupContent += `<p><strong>Precio:</strong> S/ ${data.price}</p>`;
            }

            // Botón de contacto para todos los usuarios excepto uno mismo
            if (currentUser.id !== data.userId) {
                popupContent += `
            <button onclick="initiateContact('${data.userId}', '${type}')" class="contact-btn">
                💬 Contactar
            </button>
        `;
            }

            popupContent += '</div>';
            marker.bindPopup(popupContent);

            // Guardar referencia del marcador
            markers.set(markerId, marker);

            // Si es el usuario actual, abrir popup automáticamente
            if (markerId === currentUser.id) {
                marker.openPopup();
            }
        }
        // Remover marcador
        function removeMarker(markerId) {
            if (markers.has(markerId)) {
                map.removeLayer(markers.get(markerId));
                markers.delete(markerId);
            }
        }

        // Agregar a lista de solicitudes
        function addToRequestsList(data, type) {
            const requestsList = document.getElementById('requestsList');
            const existingItem = document.getElementById(`request_${data.userId}`);

            if (existingItem) {
                existingItem.remove();
            }

            const distance = calculateDistance(
                currentUser.lat, currentUser.lng,
                data.lat, data.lng
            );

            const requestItem = document.createElement('div');
            requestItem.className = `request-item ${type}`;
            requestItem.id = `request_${data.userId}`;
            requestItem.dataset.requestData = JSON.stringify(data);

            let content = `
                <h4>${type === 'client' ? '🙋‍♂️ Cliente' : '🏍️ Mototaxista'}
                    <span class="distance-badge">${distance.toFixed(1)} km</span>
                </h4>
                <p>📍 ${data.lat.toFixed(4)}, ${data.lng.toFixed(4)}</p>
                <p>🕒 ${new Date(data.timestamp).toLocaleTimeString()}</p>
            `;

            if (type === 'client' && data.destination) {
                content += `<p>🎯 ${data.destination}</p>`;
            }

            if (data.description) {
                content += `<p>💬 ${data.description}</p>`;
            }

            if (type === 'driver' && data.price) {
                content += `<p>💰 S/ ${data.price}</p>`;
            }

            // Botones de acción
            if (currentUser.type !== type && currentUser.id !== data.userId && currentUser.status === 'waiting') {
                content += `
                    <button class="accept-btn">
                        ${type === 'client' ? 'Aceptar' : 'Solicitar'}
                    </button>
                    <button class="contact-btn" onclick="initiateContact('${data.userId}', '${type}')">
                        💬 Contactar
                    </button>
                `;
            }

            requestItem.innerHTML = content;

            // Insertar ordenado por distancia
            const items = requestsList.querySelectorAll('.request-item');
            let inserted = false;

            for (let item of items) {
                const itemData = JSON.parse(item.dataset.requestData);
                const itemDistance = calculateDistance(
                    currentUser.lat, currentUser.lng,
                    itemData.lat, itemData.lng
                );

                if (distance < itemDistance) {
                    requestsList.insertBefore(requestItem, item);
                    inserted = true;
                    break;
                }
            }

            if (!inserted) {
                requestsList.appendChild(requestItem);
            }
        }

        // Remover de lista de solicitudes
        function removeFromRequestsList(userId) {
            const item = document.getElementById(`request_${userId}`);
            if (item) {
                item.remove();
            }
        }

        // Manejar aceptación del conductor
        function handleDriverAcceptance(data) {
            if (data.targetId === currentUser.id) {
                showNotification('¡Un conductor aceptó tu solicitud!', 'success');
                currentUser.status = 'matched';
                updateUI();
            }
        }

        // Manejar mensajes de rutas
        function handleRouteMessage(data) {
            // Implementar actualización de rutas en tiempo real si es necesario
        }

        // Manejar mensajes directos
        function handleDirectMessage(data) {
            if (data.targetId === currentUser.id) {
                if (data.type === 'chat') {
                    // Mensaje de chat
                    handleChatMessage(data);
                } else {
                    // Otros tipos de mensajes
                    showNotification(data.message, data.type || 'info');

                    // Si es una solicitud cercana, resaltar en el mapa
                    if (data.type === 'nearby_request' && data.data) {
                        highlightRequest(data.data);
                    }

                    // Si es una solicitud de contacto
                    if (data.type === 'contact_request') {
                        handleContactRequest(data);
                    }
                }
            }
        }

        // Iniciar contacto con otro usuario
        window.initiateContact = function (userId, userType) {
            const targetUser = {
                id: userId,
                type: userType
            };

            // Verificar si ya hay un chat activo
            if (chatPartner && chatPartner.id === userId) {
                openChat();
                return;
            }

            // Establecer nuevo chat
            chatPartner = targetUser;
            chatMessages = [];

            // Enviar solicitud de contacto
            const contactRequest = {
                targetId: userId,
                senderId: currentUser.id,
                senderType: currentUser.type,
                message: `${currentUser.type === 'client' ? 'Cliente' : 'Taxista'} ${currentUser.id} quiere contactarte`,
                type: 'contact_request',
                timestamp: Date.now()
            };

            publishMessage(MQTT_CONFIG.topics.messages, contactRequest);

            // Abrir chat
            openChat();

            // Agregar mensaje de sistema
            addSystemMessage('Chat iniciado. Puedes enviar mensajes al ' +
                (userType === 'client' ? 'cliente' : 'taxista'));
        };

        // Manejar solicitud de contacto
        function handleContactRequest(data) {
            if (!chatPartner || chatPartner.id !== data.senderId) {
                chatPartner = {
                    id: data.senderId,
                    type: data.senderType
                };
                chatMessages = [];
            }

            // Mostrar botón de chat si no está visible
            updateChatButton();

            // Incrementar contador de mensajes no leídos
            if (!document.getElementById('chatContainer').classList.contains('active')) {
                unreadMessages++;
                updateUnreadIndicator();
            }
        }

        // Manejar mensaje de chat
        function handleChatMessage(data) {
            if (data.senderId === chatPartner?.id) {
                const message = {
                    id: Date.now(),
                    senderId: data.senderId,
                    text: data.text,
                    timestamp: data.timestamp,
                    type: 'received'
                };

                chatMessages.push(message);

                if (document.getElementById('chatContainer').classList.contains('active')) {
                    displayMessage(message);
                    scrollToBottom();
                } else {
                    unreadMessages++;
                    updateUnreadIndicator();
                }

                // Sonido de notificación
                playNotificationSound();
            }
        }

        // Abrir chat
        window.openChat = function () {
            const chatContainer = document.getElementById('chatContainer');
            chatContainer.classList.add('active');

            // Actualizar nombre del chat
            const partnerType = chatPartner.type === 'client' ? 'Cliente' : 'Taxista';
            document.getElementById('chatPartnerName').textContent = `${partnerType} ${chatPartner.id}`;

            // Limpiar mensajes no leídos
            unreadMessages = 0;
            updateUnreadIndicator();

            // Mostrar mensajes existentes
            displayAllMessages();

            // Focus en input
            document.getElementById('chatInput').focus();
        };

        // Cerrar chat
        window.closeChat = function () {
            document.getElementById('chatContainer').classList.remove('active');
        };

        // Enviar mensaje de chat
        window.sendChatMessage = function () {
            const input = document.getElementById('chatInput');
            const text = input.value.trim();

            if (!text || !chatPartner) return;

            const message = {
                id: Date.now(),
                senderId: currentUser.id,
                text: text,
                timestamp: Date.now(),
                type: 'sent'
            };

            // Agregar a mensajes locales
            chatMessages.push(message);
            displayMessage(message);
            scrollToBottom();

            // Enviar por MQTT
            const chatData = {
                targetId: chatPartner.id,
                senderId: currentUser.id,
                text: text,
                type: 'chat',
                timestamp: message.timestamp
            };

            publishMessage(MQTT_CONFIG.topics.messages, chatData);

            // Limpiar input
            input.value = '';
        };

        // Manejar tecla Enter en chat
        window.handleChatKeyPress = function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendChatMessage();
            }
        };

        // Mostrar mensaje en el chat
        function displayMessage(message) {
            const messagesContainer = document.getElementById('chatMessages');

            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${message.type}`;

            const bubbleDiv = document.createElement('div');
            bubbleDiv.className = `message-bubble ${message.type}`;

            const textP = document.createElement('p');
            textP.style.margin = '0';
            textP.textContent = message.text;

            const timeDiv = document.createElement('div');
            timeDiv.className = 'message-time';
            timeDiv.textContent = new Date(message.timestamp).toLocaleTimeString();

            bubbleDiv.appendChild(textP);
            bubbleDiv.appendChild(timeDiv);
            messageDiv.appendChild(bubbleDiv);

            messagesContainer.appendChild(messageDiv);
        }

        // Mostrar todos los mensajes
        function displayAllMessages() {
            const messagesContainer = document.getElementById('chatMessages');
            messagesContainer.innerHTML = '';

            chatMessages.forEach(message => {
                displayMessage(message);
            });

            scrollToBottom();
        }

        // Agregar mensaje del sistema
        function addSystemMessage(text) {
            const message = {
                id: Date.now(),
                senderId: 'system',
                text: text,
                timestamp: Date.now(),
                type: 'received'
            };

            chatMessages.push(message);

            if (document.getElementById('chatContainer').classList.contains('active')) {
                displayMessage(message);
                scrollToBottom();
            }
        }

        // Scroll al final del chat
        function scrollToBottom() {
            const messagesContainer = document.getElementById('chatMessages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Actualizar botón de chat
        function updateChatButton() {
            const chatBtn = document.getElementById('openChatBtn');
            const chatPartnerType = document.getElementById('chatPartnerType');

            if (chatPartner) {
                chatBtn.style.display = 'block';
                chatPartnerType.textContent = chatPartner.type === 'client' ? 'Cliente' : 'Taxista';
            } else {
                chatBtn.style.display = 'none';
            }
        }

        // Actualizar indicador de mensajes no leídos
        function updateUnreadIndicator() {
            const indicator = document.getElementById('unreadIndicator');

            if (unreadMessages > 0) {
                indicator.style.display = 'flex';
                indicator.textContent = unreadMessages > 9 ? '9+' : unreadMessages;
            } else {
                indicator.style.display = 'none';
            }
        }

        // Reproducir sonido de notificación
        function playNotificationSound() {
            // Crear un simple beep usando Web Audio API
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.value = 800;
                gainNode.gain.value = 0.1;

                oscillator.start();
                oscillator.stop(audioContext.currentTime + 0.1);
            } catch (e) {
                console.log('No se pudo reproducir sonido:', e);
            }
        }

        // Resaltar solicitud en el mapa
        function highlightRequest(requestData) {
            const marker = markers.get(requestData.userId);
            if (marker) {
                marker.openPopup();
                // Agregar efecto visual temporal
                const element = marker.getElement();
                if (element) {
                    element.classList.add('pulse');
                    setTimeout(() => {
                        element.classList.remove('pulse');
                    }, 5000);
                }
            }
        }

        // Manejar finalización de viaje
        function handleTripCompletion(data) {
            activeAssignment = null;
            currentUser.status = 'waiting';
            currentRequest = null;
            chatPartner = null;
            chatMessages = [];

            // Limpiar mapa
            clearRoute();

            // Recargar marcadores
            markers.forEach((marker, markerId) => {
                if (markerId !== currentUser.id) {
                    map.removeLayer(marker);
                }
            });
            markers.clear();
            markers.set(currentUser.id, markers.get(currentUser.id));

            updateUI();
            updateChatButton();
            closeChat();
            showNotification('Viaje completado', 'success');
        }

        // Actualizar interfaz de usuario
        function updateUI() {
            const userTypeSelect = document.getElementById('userTypeSelect');
            const formTitle = document.getElementById('formTitle');
            const actionBtn = document.getElementById('actionBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const priceGroup = document.getElementById('priceGroup');
            const userId = document.getElementById('userId');
            const userStatus = document.getElementById('userStatus');
            const selectDestinationBtn = document.getElementById('selectDestinationBtn');
            const confirmButtons = document.getElementById('confirmButtons');

            // Actualizar información del usuario
            userId.textContent = currentUser.id;
            userStatus.textContent = getStatusText(currentUser.status);

            // Deshabilitar cambio de tipo durante viaje
            userTypeSelect.disabled = currentUser.status === 'in_trip' || currentUser.status === 'requesting';

            // Actualizar según tipo de usuario
            if (currentUser.type === 'client') {
                formTitle.textContent = 'Solicitar Taxi';
                actionBtn.textContent = '📍 Actualizar Mi Ubicación';
                priceGroup.style.display = 'none';

                if (currentUser.lat && !destinationCoords && currentUser.status === 'waiting') {
                    selectDestinationBtn.style.display = 'block';
                }
            } else {
                formTitle.textContent = 'Ofrecer Servicio';
                actionBtn.textContent = '🏍️ Estar Disponible';
                priceGroup.style.display = 'block';
                selectDestinationBtn.style.display = 'none';
                confirmButtons.style.display = 'none';
            }

            // Mostrar/ocultar botón cancelar
            if (currentRequest && (currentUser.status === 'requesting' || currentUser.status === 'available')) {
                cancelBtn.style.display = 'block';
                actionBtn.disabled = true;
                selectDestinationBtn.style.display = 'none';
            } else {
                cancelBtn.style.display = 'none';
                actionBtn.disabled = currentUser.status === 'in_trip';
            }

            // Deshabilitar controles durante viaje
            if (currentUser.status === 'in_trip' || currentUser.status === 'requesting') {
                document.getElementById('destination').disabled = true;
                document.getElementById('description').disabled = true;
                document.getElementById('price').disabled = true;
            } else {
                document.getElementById('destination').disabled = false;
                document.getElementById('description').disabled = false;
                document.getElementById('price').disabled = false;
            }

            // Actualizar botón de chat
            updateChatButton();
        }

        // Obtener texto de estado
        function getStatusText(status) {
            const statusTexts = {
                'waiting': '⏳ Esperando',
                'requesting': '🚕 Solicitando taxi',
                'available': '🏍️ Disponible',
                'matched': '✅ Emparejado',
                'in_trip': '🚗 En viaje'
            };
            return statusTexts[status] || status;
        }

        // Actualizar estado de conexión
        function updateConnectionStatus(connected) {
            const statusEl = document.getElementById('connectionStatus');
            if (connected) {
                statusEl.className = 'connection-status connected';
                statusEl.textContent = '🟢 Conectado a ChasquiX';
            } else {
                statusEl.className = 'connection-status disconnected';
                statusEl.textContent = '🔴 Desconectado';
            }
        }

        // Mostrar notificación
        function showNotification(message, type = 'info') {
            // Crear elemento de notificación
            const notification = document.createElement('div');
            notification.className = 'notification';

            // Colores según tipo
            const colors = {
                'success': '#28a745',
                'error': '#dc3545',
                'warning': '#ffc107',
                'info': '#17a2b8'
            };

            notification.style.backgroundColor = colors[type] || colors.info;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Animación de entrada
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            // Remover después de 4 segundos
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        // Calcular distancia entre dos puntos (fórmula de Haversine)
        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371; // Radio de la Tierra en km
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        // Actualizar ubicación en tiempo real (cada 30 segundos)
        function startLocationTracking() {
            setInterval(() => {
                if (navigator.geolocation && (currentUser.status === 'available' || currentUser.status === 'in_trip')) {
                    navigator.geolocation.getCurrentPosition(
                        function (position) {
                            const newLat = position.coords.latitude;
                            const newLng = position.coords.longitude;

                            // Solo actualizar si la posición cambió significativamente
                            const distance = calculateDistance(currentUser.lat, currentUser.lng, newLat, newLng);
                            if (distance > 0.01) { // ~10 metros
                                updateLocation(newLat, newLng);

                                // Publicar nueva ubicación si está activo
                                if (currentRequest) {
                                    const locationUpdate = {
                                        ...currentRequest,
                                        lat: newLat,
                                        lng: newLng,
                                        timestamp: Date.now()
                                    };

                                    const topic = currentUser.type === 'client' ?
                                        MQTT_CONFIG.topics.clients : MQTT_CONFIG.topics.drivers;

                                    publishMessage(topic, locationUpdate);
                                }
                            }
                        },
                        function (error) {
                            console.log('Error obteniendo ubicación:', error);
                        }
                    );
                }
            }, 30000); // 30 segundos
        }

        // Iniciar seguimiento de ubicación al cargar
        setTimeout(startLocationTracking, 5000);

        // Limpiar al cerrar la ventana
        window.addEventListener('beforeunload', function () {
            if (currentRequest) {
                cancelCurrentRequest();
            }
            if (mqttClient && mqttClient.isConnected()) {
                mqttClient.disconnect();
            }
        });

        // Permitir que conductores soliciten a clientes
        window.requestClient = function (clientId, clientData) {
            if (currentUser.type === 'driver') {
                // Establecer chat con el cliente
                chatPartner = {
                    id: clientId,
                    type: 'client'
                };
                updateChatButton();

                // Enviar solicitud al cliente
                const driverRequest = {
                    userId: currentUser.id,
                    action: 'request_client',
                    targetClientId: clientId,
                    driverLat: currentUser.lat,
                    driverLng: currentUser.lng,
                    price: document.getElementById('price').value || '10.00',
                    description: document.getElementById('description').value.trim(),
                    timestamp: Date.now()
                };

                publishMessage(MQTT_CONFIG.topics.drivers, driverRequest);

                // Enviar mensaje directo
                const directMessage = {
                    targetId: clientId,
                    senderId: currentUser.id,
                    message: `Conductor disponible para tu viaje. Precio: S/ ${driverRequest.price}`,
                    type: 'driver_offer',
                    data: driverRequest
                };

                publishMessage(MQTT_CONFIG.topics.messages, directMessage);

                showNotification(`Oferta enviada al cliente ${clientId}`, 'success');

                // Abrir chat
                openChat();
                addSystemMessage(`Has ofrecido tu servicio al cliente. Puedes chatear para acordar detalles.`);
            }
        };

        // Función para simular datos de prueba (solo para desarrollo)
        function addTestData() {
            // Simular algunos clientes y conductores para pruebas
            const testClients = [
                {
                    userId: 'client_test_1',
                    action: 'request',
                    lat: -9.2955,
                    lng: -76.0083,
                    destinationLat: -9.2985,
                    destinationLng: -76.0053,
                    destination: 'Hospital de Tingo María',
                    description: 'Urgente, paciente embarazada',
                    timestamp: Date.now() - 120000
                },
                {
                    userId: 'client_test_2',
                    action: 'request',
                    lat: -9.2935,
                    lng: -76.0063,
                    destinationLat: -9.2915,
                    destinationLng: -76.0093,
                    destination: 'Universidad Nacional Agraria',
                    description: 'Estudiante con equipaje',
                    timestamp: Date.now() - 60000
                }
            ];

            const testDrivers = [
                {
                    userId: 'driver_test_1',
                    action: 'available',
                    lat: -9.2965,
                    lng: -76.0053,
                    description: 'Mototaxi Honda 150cc',
                    price: '12.00',
                    timestamp: Date.now() - 180000
                },
                {
                    userId: 'driver_test_2',
                    action: 'available',
                    lat: -9.2925,
                    lng: -76.0093,
                    description: 'Mototaxi Bajaj, casco incluido',
                    price: '15.00',
                    timestamp: Date.now() - 90000
                }
            ];

            // Agregar datos de prueba después de 3 segundos
            setTimeout(() => {
                testClients.forEach(client => {
                    handleClientMessage(client);
                });

                testDrivers.forEach(driver => {
                    handleDriverMessage(driver);
                });

                console.log('Datos de prueba agregados');
            }, 3000);
        }

        // Descomentar para agregar datos de prueba
        // addTestData();
