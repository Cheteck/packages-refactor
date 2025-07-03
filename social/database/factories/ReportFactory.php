<?php

namespace IJIDeals\Social\Database\Factories;

use IJIDeals\Social\Models\Post;
use IJIDeals\Social\Models\Report;
use IJIDeals\UserManagement\Models\User; // Example reportable subject
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    /**
     * @var class-string<\IJIDeals\Social\Models\Report>
     */
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $reportable = Post::factory()->create(); // Default to reporting a Post

        return [
            'reporter_id' => User::factory(),
            'reportable_id' => $reportable->id,
            'reportable_type' => get_class($reportable), // Or Post::class directly
            'reason' => $this->faker->randomElement(['spam', 'harassment', 'inappropriate_content', 'other']),
            'details' => $this->faker->paragraph,
            'status' => Report::STATUS_PENDING,
        ];
    }

    /**
     * Indicate that the report is for a specific reportable model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forReportable(\Illuminate\Database\Eloquent\Model $reportable)
    {
        return $this->state(function (array $attributes) use ($reportable) {
            return [
                'reportable_id' => $reportable->id,
                'reportable_type' => get_class($reportable),
            ];
        });
    }

    /**
     * Indicate the status of the report.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withStatus(string $status)
    {
        return $this->state([
            'status' => $status,
        ]);
    }
}
