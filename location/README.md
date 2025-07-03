# Location Package

The Location package manages all geographical data and location-based services for the IJIDeals platform. It provides a structured way to handle countries, regions, cities, and addresses.

## Core Features

-   **Geographical Data Models**: Pre-built models for `Country`, `Region`, and `City`.
-   **Address Management**: A polymorphic `Address` model that can be attached to any other model (e.g., `User`, `Shop`, `Order`).
-   **Seeders Included**: Comes with seeders to populate your database with countries, regions, and cities.
-   **Validation Rules**: Provides custom validation rules for addresses and geographical entities.
-   **Distance Calculation**: Helpers to calculate the distance between two geographical points.

## Key Components

### Models

-   `Country`: Represents a country, including its code, name, and currency information.
-   `Region`: Represents a state, province, or region within a country.
-   `City`: Represents a city within a region.
-   `Address`: A polymorphic model to store physical addresses, linked to other models. It includes fields like `street`, `postal_code`, `city_id`, etc.

### Traits

-   `HasAddresses`: A trait to easily add address management capabilities to any model.

```php
class User extends Model
{
    use HasAddresses;
}

$user->addresses()->create([...]);
```

## Dependencies

-   **`ijideals/user-management`**: For linking addresses to users.
-   **`ijideals/commerce`**: For associating addresses with shops and orders.

## Usage

1.  **Publish and run the migrations**:
    ```sh
    php artisan vendor:publish --provider="IJIDeals\Location\Providers\LocationServiceProvider" --tag="migrations"
    php artisan migrate
    ```

2.  **Run the seeders to populate geographical data**:
    ```sh
    php artisan db:seed --class="IJIDeals\Location\Database\Seeders\LocationDatabaseSeeder"
    ``` 
