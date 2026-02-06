<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Reverb Broadcasting</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-4">Reverb Broadcasting Test</h1>

            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">Connection Status:</h2>
                <div id="connection-status" class="p-3 rounded bg-yellow-100 text-yellow-800">
                    Connecting to Reverb...
                </div>
            </div>

            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">Events Log:</h2>
                <div id="events-log" class="p-4 bg-gray-50 rounded border border-gray-200 min-h-[200px] max-h-[400px] overflow-y-auto">
                    <p class="text-gray-500">Waiting for events...</p>
                </div>
            </div>

            <div class="mb-6">
                <button
                    onclick="testBroadcast()"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded"
                >
                    Trigger Test Event
                </button>
            </div>
        </div>
    </div>

    <script>
        const statusDiv = document.getElementById('connection-status');
        const eventsLog = document.getElementById('events-log');

        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const colors = {
                info: 'text-blue-600',
                success: 'text-green-600',
                error: 'text-red-600',
                event: 'text-purple-600'
            };

            const entry = document.createElement('div');
            entry.className = `mb-2 ${colors[type]}`;
            entry.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;
            eventsLog.appendChild(entry);
            eventsLog.scrollTop = eventsLog.scrollHeight;
        }

        // Clear initial message
        eventsLog.innerHTML = '';

        // Check if Echo is available
        if (typeof window.Echo !== 'undefined') {
            log('Echo instance found', 'success');
            statusDiv.className = 'p-3 rounded bg-green-100 text-green-800';
            statusDiv.textContent = 'Connected to Reverb';

            // Listen on multiple channels for testing
            window.Echo.private('orders')
                .listen('.OrderCreated', (e) => {
                    log(`OrderCreated event received on 'orders' channel: ${JSON.stringify(e)}`, 'event');
                })
                .listen('.OrderStatusUpdated', (e) => {
                    log(`OrderStatusUpdated event received on 'orders' channel: ${JSON.stringify(e)}`, 'event');
                });

            window.Echo.private('kitchen')
                .listen('.OrderCreated', (e) => {
                    log(`OrderCreated event received on 'kitchen' channel: ${JSON.stringify(e)}`, 'event');
                })
                .listen('.OrderStatusUpdated', (e) => {
                    log(`OrderStatusUpdated event received on 'kitchen' channel: ${JSON.stringify(e)}`, 'event');
                });

            window.Echo.private('bar')
                .listen('.OrderCreated', (e) => {
                    log(`OrderCreated event received on 'bar' channel: ${JSON.stringify(e)}`, 'event');
                })
                .listen('.OrderStatusUpdated', (e) => {
                    log(`OrderStatusUpdated event received on 'bar' channel: ${JSON.stringify(e)}`, 'event');
                });

            log('Subscribed to channels: orders, kitchen, bar', 'success');
        } else {
            log('Echo instance not found! Check your configuration.', 'error');
            statusDiv.className = 'p-3 rounded bg-red-100 text-red-800';
            statusDiv.textContent = 'Failed to connect to Reverb';
        }

        function testBroadcast() {
            log('Sending test broadcast request...', 'info');
            fetch('/api/test-broadcast', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    log('Test event dispatched successfully!', 'success');
                } else {
                    log('Failed to dispatch test event: ' + data.message, 'error');
                }
            })
            .catch(error => {
                log('Error triggering test: ' + error.message, 'error');
            });
        }
    </script>
</body>
</html>
