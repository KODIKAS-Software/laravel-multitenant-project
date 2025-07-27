<?php

namespace Kodikas\Multitenant\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kodikas\Multitenant\Models\Tenant;
use Kodikas\Multitenant\Models\TenantUser;

/**
 * Factory para generar relaciones tenant-usuario de prueba
 * Factory for generating test tenant-user relationships
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Kodikas\Multitenant\Models\TenantUser>
 */
class TenantUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TenantUser::class;

    /**
     * Define the model's default state.
     * Definir el estado por defecto del modelo
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => 1, // Will be overridden when used
            'user_type' => $this->faker->randomElement([
                TenantUser::TYPE_EMPLOYEE,
                TenantUser::TYPE_CLIENT,
                TenantUser::TYPE_VENDOR,
            ]),
            'role' => TenantUser::ROLE_EMPLOYEE,
            'status' => TenantUser::STATUS_ACTIVE,
            'permissions' => [],
            'joined_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'last_access_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 week', 'now'),
            'access_restrictions' => [],
            'custom_data' => [
                'department' => $this->faker->optional()->randomElement([
                    'ventas', 'marketing', 'desarrollo', 'soporte', 'administracion',
                ]),
                'position' => $this->faker->optional()->jobTitle(),
            ],
        ];
    }

    /**
     * Create owner user.
     * Crear usuario propietario
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => TenantUser::TYPE_OWNER,
            'role' => TenantUser::ROLE_SUPER_ADMIN,
            'permissions' => [
                'view_all_data',
                'manage_users',
                'manage_tenant',
                'billing_access',
                'view_dashboard',
                'view_users',
                'invite_users',
                'view_logs',
                'view_analytics',
                'export_data',
            ],
        ]);
    }

    /**
     * Create admin user.
     * Crear usuario administrador
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => TenantUser::TYPE_ADMIN,
            'role' => TenantUser::ROLE_ADMIN,
            'permissions' => [
                'view_all_data',
                'manage_users',
                'view_dashboard',
                'view_users',
                'invite_users',
                'view_analytics',
                'export_data',
            ],
        ]);
    }

    /**
     * Create employee user.
     * Crear usuario empleado
     */
    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => TenantUser::TYPE_EMPLOYEE,
            'role' => TenantUser::ROLE_EMPLOYEE,
            'permissions' => [
                'view_dashboard',
                'view_reports',
            ],
        ]);
    }

    /**
     * Create client user.
     * Crear usuario cliente
     */
    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => TenantUser::TYPE_CLIENT,
            'role' => TenantUser::ROLE_CLIENT,
            'permissions' => [
                'view_own_data',
                'create_order',
                'access_api',
            ],
        ]);
    }

    /**
     * Create vendor user.
     * Crear usuario proveedor
     */
    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => TenantUser::TYPE_VENDOR,
            'role' => TenantUser::ROLE_VIEWER,
            'permissions' => [
                'view_own_data',
                'create_product',
                'access_vendor_portal',
            ],
        ]);
    }

    /**
     * Create inactive user.
     * Crear usuario inactivo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantUser::STATUS_INACTIVE,
        ]);
    }

    /**
     * Create suspended user.
     * Crear usuario suspendido
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantUser::STATUS_SUSPENDED,
        ]);
    }

    /**
     * Add access restrictions.
     * Agregar restricciones de acceso
     */
    public function withAccessRestrictions(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_restrictions' => [
                'allowed_ips' => ['192.168.1.100', '10.0.0.50'],
                'access_hours' => ['start' => 9, 'end' => 18],
                'access_days' => [1, 2, 3, 4, 5], // Lunes a viernes
            ],
        ]);
    }
}
