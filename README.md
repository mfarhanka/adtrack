# AdTrack

Simple PHP ad tracking app for XAMPP with Bootstrap and a red theme.

## Features

- Admin login and user creation
- User login for ad operators
- Ad library with copyable ad details
- Multi-photo upload for each ad
- Auto-resize uploaded photos to max 1600px when GD is available
- Schedule-based re-advertising tracker
- Re-advertise link tracking with undo for the latest re-advertise action
- Platforms: Carousell, mudah.my, Facebook Marketplace

## Default Login

- Username: `admin`
- Password: `admin123`

## Run

1. Place the project inside your XAMPP `htdocs` folder.
2. Start Apache in XAMPP.
3. Open `http://localhost/adtrack/` in your browser.

## Storage

Data is stored in JSON files under `data/`:

- `users.json`
- `ads.json`

This keeps setup simple and avoids database configuration.