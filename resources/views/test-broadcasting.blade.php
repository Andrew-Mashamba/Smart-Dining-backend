<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcasting Test - Laravel Reverb</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Laravel Reverb Broadcasting Test</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <!-- Kitchen Display -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4 text-orange-600">Kitchen Display</h2>
                <div id="kitchen-events" class="space-y-2 text-sm">
                    <p class="text-gray-500">Waiting for events...</p>
                </div>
            </div>

            <!-- Bar Display -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4 text-blue-600">Bar Display</h2>
                <div id="bar-events" class="space-y-2 text-sm">
                    <p class="text-gray-500">Waiting for events...</p>
                </div>
            </div>

            <!-- Dashboard -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4 text-green-600">Manager Dashboard</h2>
                <div id="dashboard-events" class="space-y-2 text-sm">
                    <p class="text-gray-500">Waiting for events...</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Test Controls</h2>
            <div class="space-x-4">
                <button id="test-order-created" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    Test OrderCreated Event
                </button>
                <button id="test-order-updated" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    Test OrderStatusUpdated Event
                </button>
            </div>
        </div>

        <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4">
            <p class="text-sm text-gray-700">
                <strong>Note:</strong> This test page requires authentication and proper user roles.
                Events will only appear if you're logged in with appropriate permissions.
            </p>
        </div>
    </div>

    <script type="module">
        // Function to add event to display
        function addEvent(containerId, message, type = 'info') {
            const container = document.getElementById(containerId);
            const colors = {
                info: 'bg-blue-100 border-blue-500',
                success: 'bg-green-100 border-green-500',
                warning: 'bg-yellow-100 border-yellow-500'
            };

            const eventDiv = document.createElement('div');
            eventDiv.className = `border-l-4 p-2 mb-2 ${colors[type]}`;
            eventDiv.innerHTML = `<p class="text-xs"><strong>${new Date().toLocaleTimeString()}</strong></p><p class="text-sm">${message}</p>`;

            // Remove "waiting" message if it exists
            const waiting = container.querySelector('.text-gray-500');
            if (waiting) waiting.remove();

            container.appendChild(eventDiv);

            // Keep only last 5 events
            const events = container.querySelectorAll('.border-l-4');
            if (events.length > 5) {
                events[0].remove();
            }
        }

        // Connect to Reverb and listen for events
        if (typeof window.Echo !== 'undefined') {
            // Kitchen Channel
            Echo.private('kitchen')
                .listen('OrderCreated', (e) => {
                    console.log('Kitchen - OrderCreated:', e);
                    addEvent('kitchen-events', `New Order #${e.order_id} - Table: ${e.table}`, 'success');
                })
                .listen('OrderStatusUpdated', (e) => {
                    console.log('Kitchen - OrderStatusUpdated:', e);
                    addEvent('kitchen-events', `Order #${e.order_id} - ${e.old_status} → ${e.new_status}`, 'info');
                });

            // Bar Channel
            Echo.private('bar')
                .listen('OrderCreated', (e) => {
                    console.log('Bar - OrderCreated:', e);
                    addEvent('bar-events', `New Order #${e.order_id} - Table: ${e.table}`, 'success');
                })
                .listen('OrderStatusUpdated', (e) => {
                    console.log('Bar - OrderStatusUpdated:', e);
                    addEvent('bar-events', `Order #${e.order_id} - ${e.old_status} → ${e.new_status}`, 'info');
                });

            // Dashboard Channel (using 'orders' channel as per events)
            Echo.private('orders')
                .listen('OrderCreated', (e) => {
                    console.log('Dashboard - OrderCreated:', e);
                    addEvent('dashboard-events', `New Order #${e.order_id} - Table: ${e.table}`, 'success');
                })
                .listen('OrderStatusUpdated', (e) => {
                    console.log('Dashboard - OrderStatusUpdated:', e);
                    addEvent('dashboard-events', `Order #${e.order_id} - ${e.old_status} → ${e.new_status}`, 'info');
                });

            console.log('Echo initialized and listening on kitchen, bar, and orders channels');
        } else {
            console.error('Echo is not initialized. Check your Echo configuration.');
        }

        // Test buttons (these would need backend routes to actually dispatch events)
        document.getElementById('test-order-created').addEventListener('click', () => {
            alert('To test OrderCreated event, create an order through your application or use tinker:\n\n' +
                  'php artisan tinker\n' +
                  '$order = App\\Models\\Order::first();\n' +
                  'event(new App\\Events\\OrderCreated($order));');
        });

        document.getElementById('test-order-updated').addEventListener('click', () => {
            alert('To test OrderStatusUpdated event, update an order status through your application or use tinker:\n\n' +
                  'php artisan tinker\n' +
                  '$order = App\\Models\\Order::first();\n' +
                  'event(new App\\Events\\OrderStatusUpdated($order, "pending", "preparing"));');
        });
    </script>
</body>
</html>
