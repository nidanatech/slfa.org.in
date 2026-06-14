# Channel Integration Reference Guide

This document outlines the standard requirements and patterns for integrating new communication channels (e.g., Instagram, WhatsApp, SMS) into the Unified Inbox.

## 1. Data Models & Mapping

### Conversation Model
The `Conversation` record is the parent container for all interactions.

- **`channel_id`**: (Required) FK to the `channels` table.
- **`contact_id`**: (Required) FK to the `contacts` table.
- **`last_message_at`**: (**CRITICAL**) Must be set to the timestamp of the *most recent message* in the thread. 
    - **Why?**: The Inbox uses this field to sort discussions. If NULL, the conversation will be buried at the bottom.
    - **Logic**: Set it explicitly during `create()` and update it whenever a new `Message` is added.
- **`status`**: Default to `'open'`. Statuses: `open`, `closed`, `snoozed`.
- **`created_at`/`updated_at`**: Standard timestamps.

**Example:**
```php
$conversation = Conversation::firstOrCreate(
    ['channel_id' => $channel->id, 'contact_id' => $contact->id],
    [
        'tenant_id' => $channel->tenant_id,
        'status' => 'open',
        'created_at' => $sourceTimestamp, 
        'updated_at' => $sourceTimestamp,
        'last_message_at' => $sourceTimestamp, // <--- DO NOT FORGET
    ]
);
```

### Message Model
Individual messages within the conversation.

- **`conversation_id`**: FK to parent.
- **`direction`**: `'inbound'` (from customer) or `'outbound'` (from agent).
- **`type`**: `'text'`, `'image'`, `'email'`, etc.
- **`content`**: The message body.
- **`meta`**: JSON column for channel-specific data.
    - **`external_id`**: Store the source platform's ID (e.g., Tweet ID, Email Message ID) to prevent duplicates.
    - Channel-specific fields (e.g., `subject`, `is_html`, `sender_handle`).

### Contact Model
The person/entity communicating.

- **`external_id`**: (Required) Unique identifier from the source if available (e.g., Instagram User ID). If none, generate a UUID.
- **`name`**: Display name.
- **`emails`**: JSON array of email objects. `[{ "value": "x@y.com", "type": "work" }]`.
- **`avatar_url`**: Profile picture from source.

## 2. Synchronization Strategy

### Handling Inbound Data
When fetching data from an external API (Sync Job or Webhook):

1.  **Resolve Contact**:
    - Try to find existing contact by `external_id` or `email`.
    - If strictly matching by opaque ID (e.g., Instagram ID), search `meta->source_id` or `external_id`.
    - If creating new: **Generate `external_id` (UUID)** if the source doesn't provide a robust unique string. This prevents SQL errors.

2.  **Resolve/Create Conversation**:
    - `firstOrCreate` by `channel_id` + `contact_id`.
    - Ensure `last_message_at` is populated.

3.  **Deduplication**:
    - Before creating a `Message`, check if it exists using `meta->external_id`.
    - **Idempotency** is key: The sync job may run multiple times on the same data.

4.  **Update Timestamps**:
    - If the new message is newer than the conversation's `last_message_at`, update the conversation:
      ```php
      if ($messageTime > $conversation->last_message_at) {
          $conversation->update(['last_message_at' => $messageTime]);
      }
      ```

### Handling Outbound (Sending)
- When user replies, create `Message` (`direction: outbound`).
- Immediately dispatch job/service to send to external API.
- Update `last_message_at` to `now()`.

## 3. Common Pitfalls
- **Missing `last_message_at`**: Breaks inbox sorting.
- **Missing `external_id` on Contact**: Causes SQL Insert errors (Fatal).
- **Duplicate Messages**: Failing to check `meta->id` before insert causes spam in UI.
- **Timezones**: Always store timestamps in UTC (App default) but respect source timezone logic if provided.
