<?php

namespace Kodikas\Multitenant\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kodikas\Multitenant\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Factory para generar tenants de prueba
 * Factory for generating test tenants
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kodikas\Multitenant\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     * Definir el estado por defecto del modelo
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();
        $slug = Str::slug($name);

        return [
            'name' => $name,
            'slug' => $slug,
            'subdomain' => $slug,
            'status' => Tenant::STATUS_ACTIVE,
            'plan' => $this->faker->randomElement(['basic', 'pro', 'enterprise']),
            'trial_ends_at' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'subscription_ends_at' => $this->faker->dateTimeBetween('+1 month', '+1 year'),
            'settings' => [
                'app_name' => $name,
                'locale' => $this->faker->randomElement(['es', 'en']),
                'timezone' => $this->faker->randomElement([
                    'America/Mexico_City',
                    'America/New_York',
                    'Europe/Madrid'
                ]),
            ],
            'limits' => [
                'users' => $this->faker->numberBetween(5, 100),
                'storage' => $this->faker->randomElement(['1GB', '10GB', '100GB']),
                'api_calls' => $this->faker->numberBetween(1000, 100000),
            ],
            'custom_data' => [
                'industry' => $this->faker->randomElement([
                    'technology', 'retail', 'healthcare', 'finance', 'education'
                ]),
                'employee_count' => $this->faker->numberBetween(1, 1000),
            ],
        ];
    }

    /**
     * Indicate that the tenant is on trial.
     * Indicar que el tenant está en período de prueba
     *
     * @return static
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tenant::STATUS_TRIAL,
            'trial_ends_at' => $this->faker->dateTimeBetween('now', '+14 days'),
            'subscription_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the tenant is inactive.
     * Indicar que el tenant está inactivo
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tenant::STATUS_INACTIVE,
        ]);
    }

    /**
     * Indicate that the tenant is suspended.
     * Indicar que el tenant está suspendido
     *
     * @return static
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tenant::STATUS_SUSPENDED,
        ]);
    }

    /**
     * Create tenant with specific plan.
     * Crear tenant con plan específico
     *
     * @param string $plan Plan name / Nombre del plan
     * @return static
     */
    public function withPlan(string $plan): static
    {
        $planLimits = config("multitenant.billing.plans.{$plan}.features", []);

        return $this->state(fn (array $attributes) => [
            'plan' => $plan,
            'limits' => $planLimits,
        ]);
    }

    /**
     * Create tenant with custom domain.
     * Crear tenant con dominio personalizado
     *
     * @param string $domain Custom domain / Dominio personalizado
     * @return static
     */
    public function withDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => $domain,
            'subdomain' => null,
        ]);
    }
}
