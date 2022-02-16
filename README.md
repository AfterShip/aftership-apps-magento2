# AfterShip extension for Magento 2

AfterShip extension for Magento 2. Allows connect with AfterShip and more.

## Prerequisites

Magento 2

### Install

  -  log into the Magento 2 server and cd into the root directory of the Magento app:
    -  Execute the following commands:
      - composer require aftership/aftership-apps-magento2
      - php bin/magento module:enable AfterShip_Tracking --clear-static-content
      - php bin/magento setup:upgrade
      - php bin/magento setup:static-content:deploy -f

### Setup
  - From admin:
    - Go to stores > configuration
    - Find AfterShip in sidebar
    - Open Connect
    - Click the "Connect Now", Navigate to AfterShip and connect store
