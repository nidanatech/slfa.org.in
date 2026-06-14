---
description: How to activate the WhatsApp Baileys service
---
### Steps to Activate WhatsApp Service

1. **Open a new terminal** and navigate to the `whatsapp-service` directory:
   ```powershell
   cd c:\mine\SydkicV2\whatsapp-service
   ```

2. **Install dependencies** (if not already done):
   ```powershell
   npm install
   ```

3. **Start the service**:
   ```powershell
   npm start
   ```

The service will run on `http://localhost:3001` and handle WhatsApp connections for Sydkic. Ensure your Laravel application is running on `http://localhost:8000`.
