# Bytars School - Directus SSO Integration for Nextcloud

This Nextcloud app enables Single Sign-On (SSO) integration with Directus, allowing users to authenticate using their Directus credentials.

## Features

- **Single Sign-On**: Users can log in to Nextcloud using their Directus credentials
- **User Synchronization**: Automatically sync user information from Directus
- **Auto-Provisioning**: Optionally create new Nextcloud users automatically when they first log in via Directus
- **Group Management**: Assign users to default groups automatically
- **Admin Interface**: Easy configuration through Nextcloud admin settings

## Installation

1. Clone or download this app to your Nextcloud `apps` directory:
   ```bash
   cd /path/to/nextcloud/apps
   git clone https://github.com/your-repo/bytarsschool-nextcloud.git bytarsschool
   ```

2. Install dependencies:
   ```bash
   cd bytarsschool
   composer install --no-dev
   ```

3. Enable the app in Nextcloud:
   - Go to Apps in your Nextcloud admin interface
   - Find "Bytars School" and enable it

## Configuration

### Prerequisites

- A running Directus instance
- Admin access token from Directus
- Nextcloud admin privileges

### Setup Steps

1. **Get Directus Admin Token**:
   - Log in to your Directus admin panel
   - Go to Settings > API Keys
   - Create a new token with admin privileges
   - Copy the token for use in Nextcloud

2. **Configure the Integration**:
   - Go to Nextcloud Admin Settings
   - Navigate to Security section
   - Find "Bytars School - Directus Integration"
   - Enter your configuration:
     - **Directus URL**: Your Directus instance URL (e.g., `https://your-directus.com`)
     - **Admin Token**: The admin token from Directus
     - **Default Group**: Optional group for new users
     - **Auto-provision users**: Enable to automatically create users

3. **Test Connection**:
   - Click "Test Connection" to verify your settings
   - Ensure the connection is successful before proceeding

### Directus User Requirements

For users to authenticate successfully, they must:
- Have an active account in Directus (`status: 'active'`)
- Have a valid email address
- Be able to authenticate with the Directus `/auth/login` endpoint

## How It Works

### Authentication Flow

1. User tries to log in to Nextcloud
2. If the user doesn't exist in Nextcloud and auto-provisioning is enabled, the user is created
3. Nextcloud validates credentials against Directus using the `/auth/login` endpoint
4. If authentication succeeds, the user is logged into Nextcloud
5. User information is synchronized from Directus (name, email, etc.)

### User Synchronization

The app synchronizes the following user information:
- **Display Name**: Combination of `first_name` and `last_name` from Directus
- **Email Address**: User's email from Directus
- **Account Status**: Based on Directus user status

### Security Considerations

- The Directus admin token is stored securely in Nextcloud's configuration
- All API calls to Directus are made server-side
- User passwords are never stored in Nextcloud (authentication is delegated to Directus)

## API Endpoints Used

This app interacts with the following Directus endpoints:

- `POST /auth/login` - For user authentication
- `GET /users/me` - For admin token validation
- `GET /users` - For user information retrieval and search
- `GET /users?filter[email][_eq]={email}` - For specific user lookup

## Troubleshooting

### Connection Issues

- Verify Directus URL is correct and accessible
- Check that the admin token has sufficient permissions
- Ensure SSL certificates are valid (or disable SSL verification for testing)

### Authentication Problems

- Verify user exists in Directus with 'active' status
- Check that user email matches between systems
- Review Nextcloud logs for detailed error messages

### Auto-Provisioning Issues

- Ensure auto-provisioning is enabled in settings
- Check that default group exists or can be created
- Verify admin token has permission to read user data

## Development

### Project Structure

```
lib/
├── AppInfo/
│   └── Application.php          # Main application bootstrap
├── Controller/
│   ├── PageController.php       # Basic page controller
│   └── SettingsController.php   # Admin settings API
├── Settings/
│   └── Admin.php               # Admin settings interface
├── User/
│   └── UserProvisioning.php    # User creation and group management
└── UserBackend/
    └── DirectusUserBackend.php # Main authentication backend
```

### Testing

Run the test suite:
```bash
composer test:unit
```

### Code Quality

Run code analysis:
```bash
composer cs:check
composer psalm
```

## License

This project is licensed under the AGPL-3.0-or-later license.

## Support

For issues and questions:
- Create an issue on GitHub
- Contact: contact@bytars.com

A template to get started with Nextcloud app development.

## Usage

- To get started easily use the [Appstore App generator](https://apps.nextcloud.com/developer/apps/generate) to
  dynamically generate an App based on this repository with all the constants prefilled.
- Alternatively you can use the "Use this template" button on the top of this page to create a new repository based on
  this repository. Afterwards adjust all the necessary constants like App ID, namespace, descriptions etc.

Once your app is ready follow the [instructions](https://nextcloudappstore.readthedocs.io/en/latest/developer.html) to
upload it to the Appstore.

## Resources

### Documentation for developers:

- General documentation and tutorials: https://nextcloud.com/developer
- Technical documentation: https://docs.nextcloud.com/server/latest/developer_manual

### Help for developers:

- Official community chat: https://cloud.nextcloud.com/call/xs25tz5y
- Official community forum: https://help.nextcloud.com/c/dev/11
