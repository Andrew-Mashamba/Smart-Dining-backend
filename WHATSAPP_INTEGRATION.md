# WhatsApp Integration for Order Receiving

## Overview

This document describes the WhatsApp Business API integration that allows customers to place orders directly through WhatsApp messages.

## Features

- **Menu Viewing**: Customers can request the full menu via WhatsApp
- **Order Placement**: Parse customer messages to create orders in the system
- **Order Confirmation**: Automatic confirmation messages with order details
- **Status Updates**: Automatic notifications when order status changes
- **Guest Management**: Automatic guest creation based on phone number
- **Help System**: Contextual help for customers

## Setup Instructions

### 1. WhatsApp Business API Account

1. Create a Meta Business Account at [Meta for Developers](https://developers.facebook.com/)
2. Set up WhatsApp Business API
3. Get your credentials:
   - Access Token (API Token)
   - Phone Number ID
   - Generate a Verify Token (use a secure random string)

### 2. Environment Configuration

Add the following variables to your `.env` file:

```env
WHATSAPP_API_TOKEN=your_access_token_here
WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id_here
WHATSAPP_VERIFY_TOKEN=your_secure_verify_token_here
```

### 3. Webhook Configuration

Configure your WhatsApp webhook in the Meta Developer Console:

- **Webhook URL**: `https://yourdomain.com/webhooks/whatsapp`
- **Verify Token**: The same token you set in `WHATSAPP_VERIFY_TOKEN`
- **Webhook Fields**: Subscribe to `messages`

### 4. Package Installation

The WhatsApp SDK is already installed via Composer:

```bash
composer require netflie/whatsapp-cloud-api
```

## Usage

### Customer Commands

Customers can send the following commands via WhatsApp:

#### 1. View Menu
```
menu
```
Returns a formatted list of all available menu items with prices.

#### 2. Place Order
```
order Pizza x 2, Burger x 1
```
Format: `order [item name] x [quantity], [item name] x [quantity]`

- Creates a new guest if phone number is not recognized
- Validates menu items
- Creates order with status 'pending'
- Calculates totals including tax
- Sends confirmation with order number and estimated time

#### 3. Check Status
```
status
```
Returns the status of the customer's most recent order.

#### 4. Get Help
```
help
```
Returns a list of available commands with examples.

#### 5. Unknown Commands
Any unrecognized message will trigger a help message.

## System Behavior

### Order Creation Flow

1. **Message Received**: WhatsApp sends webhook POST request
2. **Parse Message**: Extract phone number and message text
3. **Guest Lookup/Creation**:
   - Search for existing guest by phone number
   - Create new guest if not found
4. **Parse Order Items**:
   - Match item names (case-insensitive, partial match)
   - Validate availability
   - Extract quantities
5. **Create Order**:
   - Set `order_source` to 'whatsapp'
   - Set `status` to 'pending'
   - Create order items
   - Calculate totals (subtotal + 18% tax)
6. **Send Confirmation**: WhatsApp message with order details

### Status Update Notifications

When an order status changes, automatic WhatsApp notifications are sent:

- **preparing**: "Your order is now being prepared..."
- **ready**: "Your order is ready for pickup..."
- **completed**: "Thank you for your order..."
- **cancelled**: "Your order has been cancelled..."

This is handled automatically via the `OrderObserver` class.

## Technical Implementation

### Files

1. **Controller**: `app/Http/Controllers/WhatsAppController.php`
   - Handles webhook verification (GET)
   - Processes incoming messages (POST)
   - Routes commands to appropriate handlers

2. **Service**: `app/Services/WhatsAppService.php`
   - Sends WhatsApp messages
   - Formats menu text
   - Processes orders
   - Parses order text
   - Sends confirmations and status updates

3. **Observer**: `app/Observers/OrderObserver.php`
   - Monitors order status changes
   - Triggers WhatsApp notifications for WhatsApp orders

4. **Routes**: `routes/web.php`
   ```php
   Route::get('/webhooks/whatsapp', [WhatsAppController::class, 'verify']);
   Route::post('/webhooks/whatsapp', [WhatsAppController::class, 'webhook']);
   ```

5. **Configuration**: `config/services.php`
   ```php
   'whatsapp' => [
       'api_token' => env('WHATSAPP_API_TOKEN'),
       'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
       'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
   ],
   ```

### Database Schema

The integration uses existing models:

- **Guest**: Stores customer information (phone_number, name, etc.)
- **Order**: Stores order details with `order_source='whatsapp'`
- **OrderItem**: Stores individual items in the order
- **MenuItem**: Menu items that customers can order

## Testing

Comprehensive tests are located in:
- `tests/Feature/WhatsApp/OrderReceivingTest.php`
- `tests/Feature/WhatsAppIntegrationTest.php`

Run tests with:
```bash
php artisan test --filter=WhatsApp
```

### Test Coverage

- Webhook verification (valid/invalid tokens)
- Message receiving and parsing
- Order creation from WhatsApp messages
- Guest creation for new phone numbers
- Menu command
- Help command
- Status command
- Unknown command handling
- Order status update notifications
- Non-text message handling

## Security Considerations

1. **Webhook Verification**: The verify token ensures only Meta can access your webhook
2. **Input Validation**: All user input is validated before processing
3. **Error Handling**: Graceful error handling prevents system crashes
4. **Rate Limiting**: Consider implementing rate limiting to prevent abuse
5. **Phone Number Validation**: Ensure phone numbers are properly formatted

## Example Flows

### New Customer Orders

1. Customer sends: "order Pizza x 2"
2. System creates guest with phone number
3. System finds menu item "Pizza"
4. Order created with status 'pending'
5. Customer receives confirmation with order number
6. Kitchen updates status to 'preparing'
7. Customer receives: "Your order is being prepared..."
8. Kitchen updates status to 'ready'
9. Customer receives: "Your order is ready for pickup..."

### Returning Customer

1. Customer sends: "menu"
2. System sends formatted menu
3. Customer sends: "order Burger x 1"
4. System finds existing guest
5. Order created and confirmed
6. Status updates sent automatically

## Troubleshooting

### Webhook Not Receiving Messages

1. Verify webhook URL is correct in Meta Developer Console
2. Check that webhook is verified (green checkmark)
3. Ensure your server is accessible from the internet
4. Check Laravel logs: `storage/logs/laravel.log`

### Orders Not Being Created

1. Check database connection
2. Verify menu items exist and are 'available'
3. Check logs for parsing errors
4. Ensure order text format is correct

### Messages Not Sending

1. Verify `WHATSAPP_API_TOKEN` is correct
2. Check `WHATSAPP_PHONE_NUMBER_ID` is valid
3. Ensure phone number format includes country code
4. Check WhatsApp API quota limits
5. Review logs for API errors

### Testing Locally

Use tools like ngrok to expose your local server:

```bash
ngrok http 8000
```

Then use the ngrok URL in Meta Developer Console webhook settings.

## API Response Codes

- `200 OK`: Message processed successfully
- `403 Forbidden`: Webhook verification failed
- `500 Error`: Internal server error (check logs)

## Monitoring

Monitor the following:

1. **Order Creation Rate**: Track orders from 'whatsapp' source
2. **Error Rates**: Monitor application logs
3. **Response Times**: Ensure webhook responds within 20 seconds
4. **Message Delivery**: Track WhatsApp API success/failure rates

## Future Enhancements

Potential improvements:

1. **Interactive Buttons**: Use WhatsApp interactive messages
2. **Order Modification**: Allow customers to modify pending orders
3. **Payment Integration**: Accept payments via WhatsApp
4. **Multi-language Support**: Detect customer language preference
5. **Order History**: Let customers view past orders
6. **Loyalty Points**: Display and redeem loyalty points
7. **Media Support**: Allow customers to send images/voice notes

## Support

For issues or questions:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Review Meta Developer Console for webhook errors
3. Run tests to verify functionality
4. Check database records for order/guest data

## References

- [WhatsApp Cloud API Documentation](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [netflie/whatsapp-cloud-api Package](https://github.com/netflie/whatsapp-cloud-api)
- [Laravel Documentation](https://laravel.com/docs)
