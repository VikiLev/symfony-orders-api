# Orders Management System

An online store order management system built with Symfony.

## Overview

This project implements a REST API for managing orders, including CRUD operations, filtering, searching, and pagination. Event-driven email notifications are also implemented for order status changes.

## API Endpoints

### Get list of orders

Supports the following query parameters:
- `page` — page number
- `limit` — number of items per page
- `status` — filter by status (`pending`, `processing`, `shipped`, `delivered`, `cancelled`)
- `date_from` — filter from date (format `Y-m-d`)
- `date_to` — filter to date (format `Y-m-d`)
- `email` — search by customer email

**Example request:**  
`GET /api/orders?page=1&limit=10&status=pending&email=test@example.com`

---

### Get order details
GET /api/orders/{id}

### Create an order
POST /api/orders

`{
"customerName": "9",
"customerEmail": "name6@mail.com",
"totalAmount": "1",
"items": [
{
"productName": "produc65",
"quantity": "1",
"price": "1"
}
]
}`


### Update an order
PUT /api/orders/{id}

### Delete an order
DELETE /api/orders/{id}

### Change order status
PATCH /api/orders/{id}/status

`{
"status": "shipped"
}`

`Available statuses:
pending
processing
shipped
delivered
cancelled`

###  Events and Worker
OrderStatusChangedEvent — event triggered when an order status changes.
EmailNotificationHandler — worker that sends emails:
On order creation — welcome email
On status change to shipped — shipping notification
On status change to delivered — thank-you email

Logs all set messages
