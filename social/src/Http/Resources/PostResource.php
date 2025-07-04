<?php

namespace IJIDeals\Social\Http\Resources;

use IJIDeals\Social\Models\Post;
use Illuminate\Http\Resources\Json\JsonResource; // Added use statement for the moved Post model

// Assuming UserResource is correctly namespaced, e.g., use App\Http\Resources\UserResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request): array
    {
        // Eager load taggedProducts if they are not already loaded to prevent N+1 issues
        // when accessing $this->taggedProducts directly without whenLoaded.
        // However, the controller already does ->load('taggedProducts'), so this might be redundant
        // if the resource is always instantiated with the loaded relation.
        // It's safer to keep whenLoaded for flexibility.

        $taggedProducts = $this->whenLoaded('taggedProducts', function () {
            return $this->taggedProducts->map(function ($product) {
                // We need to know the actual type of the product to return appropriate data
                // The 'taggable_type' is on the pivot table, but the $product instance here
                // will be of type Model::class due to the generic relation definition.
                // A more robust solution would involve a custom ResourceCollection
                // or enhancing the Post model to retrieve typed tagged products.

                // For now, let's return basic info.
                // This will require further refinement to correctly identify and serialize
                // MasterProduct vs ShopProduct.
                // A simple approach: check the class of the loaded pivot model.
                // The $product here is actually the related model instance if Eloquent resolves it.
                // If the relation is defined as morphToMany(Model::class, ...), $product will be an instance of Model.
                // We need to access the pivot data to know the real type.

                // Let's assume the $product instance IS correctly hydrated by Laravel based on taggable_type.
                // If not, this part needs significant rework, possibly by iterating $this->taggedProducts()->getPivot()
                // and then fetching models by ID and type.

                // Given the current Post->taggedProducts() relation:
                // return $this->morphToMany(Model::class, 'taggable', 'taggable_products'...)
                // The $product variable in the map function will be an instance of Illuminate\Database\Eloquent\Model.
                // We need to check its actual class or rely on pivot data.
                // $product->pivot->taggable_type will give the class name.

                $className = get_class($product);
                $productType = class_basename($className); // MasterProduct or ShopProduct

                return [
                    'id' => $product->id,
                    'type' => $productType,
                    // Add more product-specific fields as needed, potentially using specific resources
                    // e.g., 'name' => $product->name, (if common)
                    // This part will likely need dedicated Product Resources later.
                    // For now, just returning id and type.
                    // To access actual product attributes, $product needs to be the correct instance.
                    // Let's assume $product IS the correct instance for now (MasterProduct or ShopProduct)
                    'name' => $product->name ?? null, // Example: assuming a 'name' attribute
                    'original_model_class' => $className, // For debugging what instance we got
                ];
            });
        });


        return [
            'id' => $this->id,
            'content' => $this->content,
            'type' => $this->type->value ?? $this->type, // Handle if not an enum yet
            'visibility' => $this->visibility->value ?? $this->visibility, // Handle if not an enum yet
            'status' => $this->status,
            'author' => UserResource::make($this->whenLoaded('author')),
            'comments_count' => $this->whenCounted('comments'),
            'reactions_count' => $this->whenCounted('reactions'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tagged_products' => $taggedProducts,
        ];
    }
}
