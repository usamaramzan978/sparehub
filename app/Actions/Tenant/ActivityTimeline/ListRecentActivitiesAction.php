<?php

declare(strict_types=1);

namespace App\Actions\Tenant\ActivityTimeline;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ListRecentActivitiesAction
{
    private const TENANT_CONNECTION = 'tenant';

    private const TIMELINE_TABLE = 'tenant_activity_timelines';

    /**
     * @return Collection<int, array{event: string, title: string, description: string, badge_class: string, time: string, date: string}>
     */
    public function handle(int $limit = 25): Collection
    {
        $connection = tenancy()->initialized ? self::TENANT_CONNECTION : config('database.default');

        if (! is_string($connection) || $connection === '') {
            return collect();
        }

        if (! Schema::connection($connection)->hasTable(self::TIMELINE_TABLE)) {
            return collect();
        }

        return DB::connection($connection)->table(self::TIMELINE_TABLE)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (object $activity): array {
                $properties = json_decode((string) ($activity->properties ?? '{}'), true);
                $event = (string) ($activity->event ?? 'activity');
                $meta = $this->eventMeta($event);

                return [
                    'event' => $event,
                    'title' => $meta['title'],
                    'description' => $this->resolveDescription($event, is_array($properties) ? $properties : [], (string) $activity->description),
                    'badge_class' => $meta['badge_class'],
                    'time' => Date::parse((string) $activity->created_at)->format('h:i A'),
                    'date' => Date::parse((string) $activity->created_at)->format('d M Y'),
                ];
            });
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function resolveDescription(string $event, array $properties, string $fallback): string
    {
        return match ($event) {
            'auth_login_succeeded' => 'User login completed successfully.',
            'auth_logout' => 'User logged out.',
            'pos_sale_created' => sprintf(
                'POS sale created: %s.',
                (string) ($properties['invoice_no'] ?? 'invoice')
            ),
            'sale_created' => sprintf(
                'Sale created: %s.',
                (string) ($properties['invoice_no'] ?? 'invoice')
            ),
            'sale_updated' => sprintf(
                'Sale updated: %s.',
                (string) ($properties['invoice_no'] ?? 'invoice')
            ),
            'sale_deleted' => sprintf(
                'Sale deleted: %s.',
                (string) ($properties['invoice_no'] ?? 'invoice')
            ),
            'sale_payment_recorded' => sprintf(
                'Sale payment recorded (%.2f).',
                (float) ($properties['amount'] ?? 0)
            ),
            'sale_payment_updated' => sprintf(
                'Sale payment updated (%.2f).',
                (float) ($properties['amount'] ?? 0)
            ),
            'sale_payment_deleted' => sprintf(
                'Sale payment deleted (%.2f).',
                (float) ($properties['amount'] ?? 0)
            ),
            'purchase_created' => sprintf(
                'Purchase created: %s.',
                (string) ($properties['purchase_no'] ?? 'purchase')
            ),
            'purchase_updated' => sprintf(
                'Purchase updated: %s.',
                (string) ($properties['purchase_no'] ?? 'purchase')
            ),
            'purchase_deleted' => sprintf(
                'Purchase deleted: %s.',
                (string) ($properties['purchase_no'] ?? 'purchase')
            ),
            'vendor_payment_recorded' => sprintf(
                'Vendor payment recorded (%.2f).',
                (float) ($properties['amount'] ?? 0)
            ),
            'vendor_payment_updated' => sprintf(
                'Vendor payment updated (%.2f).',
                (float) ($properties['amount'] ?? 0)
            ),
            'vendor_payment_deleted' => sprintf(
                'Vendor payment deleted (%.2f).',
                (float) ($properties['amount'] ?? 0)
            ),
            'purchase_return_created' => sprintf(
                'Purchase return created: %s.',
                (string) ($properties['return_no'] ?? 'return')
            ),
            'purchase_return_updated' => sprintf(
                'Purchase return updated: %s.',
                (string) ($properties['return_no'] ?? 'return')
            ),
            'purchase_return_deleted' => sprintf(
                'Purchase return deleted: %s.',
                (string) ($properties['return_no'] ?? 'return')
            ),
            'job_card_created' => sprintf(
                'Job card created: %s.',
                (string) ($properties['job_no'] ?? 'job card')
            ),
            'job_card_updated' => sprintf(
                'Job card updated: %s.',
                (string) ($properties['job_no'] ?? 'job card')
            ),
            'job_card_deleted' => sprintf(
                'Job card deleted: %s.',
                (string) ($properties['job_no'] ?? 'job card')
            ),
            'expense_created' => sprintf(
                'Expense recorded: %s (%.2f).',
                (string) ($properties['title'] ?? 'expense'),
                (float) ($properties['amount'] ?? 0)
            ),
            'expense_updated' => sprintf(
                'Expense updated: %s (%.2f).',
                (string) ($properties['title'] ?? 'expense'),
                (float) ($properties['amount'] ?? 0)
            ),
            'expense_deleted' => sprintf(
                'Expense deleted: %s (%.2f).',
                (string) ($properties['title'] ?? 'expense'),
                (float) ($properties['amount'] ?? 0)
            ),
            'inventory_stock_adjusted' => sprintf(
                'Stock adjusted for %s (%s %.2f).',
                (string) ($properties['product_name'] ?? 'product'),
                (string) ($properties['action'] ?? 'set'),
                (float) ($properties['requested_qty'] ?? 0)
            ),
            'branch_switched' => sprintf(
                'Branch switched to %s.',
                (string) ($properties['to_branch_name'] ?? 'selected branch')
            ),
            'branch_created' => sprintf(
                'Branch created: %s.',
                (string) ($properties['branch_name'] ?? 'new branch')
            ),
            'branch_updated' => sprintf(
                'Branch updated: %s.',
                (string) ($properties['branch_name'] ?? 'branch')
            ),
            'branch_deleted' => sprintf(
                'Branch deleted: %s.',
                (string) ($properties['branch_name'] ?? 'branch')
            ),
            'settings_updated' => sprintf(
                'Settings updated (%s).',
                implode(', ', array_slice((array) ($properties['changed_attributes'] ?? []), 0, 4))
            ),
            'two_factor_policy_changed' => sprintf(
                '2FA policy changed to %s (%s).',
                (string) ($properties['current_method'] ?? 'none'),
                (bool) ($properties['current_enabled'] ?? false) ? 'enabled' : 'disabled'
            ),
            'two_step_code_issued' => 'Two-step code sent to email.',
            'two_step_challenge_required' => 'Two-step verification challenge required.',
            'two_factor_enrollment_required' => 'Authenticator setup is required before access.',
            'two_step_verified' => 'Two-step verification completed.',
            'two_step_verification_failed' => 'Two-step verification failed.',
            'two_factor_enrollment_started' => 'Authenticator enrollment started.',
            'two_factor_enrollment_verified' => 'Authenticator enrollment verified.',
            'two_factor_secret_reset' => 'Authenticator secret was reset.',
            'two_factor_backup_codes_regenerated' => 'Backup codes regenerated.',
            'profile_updated' => 'User profile updated.',
            'user_created' => sprintf('User created: %s.', (string) ($properties['user_email'] ?? 'new user')),
            'user_updated' => sprintf('User updated: %s.', (string) ($properties['user_email'] ?? 'user')),
            'user_deleted' => sprintf('User deleted: %s.', (string) ($properties['user_email'] ?? 'user')),
            'auth_failure' => sprintf(
                'Failed login attempt for %s.',
                (string) ($properties['email'] ?? 'unknown user')
            ),
            default => $fallback !== '' ? $fallback : 'Activity recorded.',
        };
    }

    /**
     * @return array{title: string, badge_class: string}
     */
    private function eventMeta(string $event): array
    {
        return match ($event) {
            'auth_login_succeeded', 'auth_logout' => ['title' => 'Auth', 'badge_class' => 'bg-success-transparent text-success'],
            'pos_sale_created', 'sale_created', 'sale_updated', 'sale_deleted', 'sale_payment_recorded', 'sale_payment_updated', 'sale_payment_deleted' => [
                'title' => 'Sales',
                'badge_class' => 'bg-success-transparent text-success',
            ],
            'purchase_created', 'purchase_updated', 'purchase_deleted', 'vendor_payment_recorded', 'vendor_payment_updated', 'vendor_payment_deleted', 'purchase_return_created', 'purchase_return_updated', 'purchase_return_deleted' => [
                'title' => 'Purchases',
                'badge_class' => 'bg-warning-transparent text-warning',
            ],
            'job_card_created', 'job_card_updated', 'job_card_deleted' => [
                'title' => 'Workshop',
                'badge_class' => 'bg-primary-transparent text-primary',
            ],
            'expense_created', 'expense_updated', 'expense_deleted' => [
                'title' => 'Expenses',
                'badge_class' => 'bg-info-transparent text-info',
            ],
            'inventory_stock_adjusted' => [
                'title' => 'Inventory',
                'badge_class' => 'bg-secondary-transparent text-muted',
            ],
            'branch_switched', 'branch_created', 'branch_updated', 'branch_deleted' => [
                'title' => 'Branch',
                'badge_class' => 'bg-primary-transparent text-primary',
            ],
            'settings_updated' => ['title' => 'Settings', 'badge_class' => 'bg-info-transparent text-info'],
            'two_factor_policy_changed' => ['title' => '2FA Policy', 'badge_class' => 'bg-warning-transparent text-warning'],
            'two_step_code_issued', 'two_step_challenge_required', 'two_factor_enrollment_required', 'two_step_verified', 'two_factor_enrollment_started', 'two_factor_enrollment_verified', 'two_factor_secret_reset', 'two_factor_backup_codes_regenerated' => [
                'title' => '2FA Security',
                'badge_class' => 'bg-success-transparent text-success',
            ],
            'two_step_verification_failed' => ['title' => '2FA Security', 'badge_class' => 'bg-danger-transparent text-danger'],
            'profile_updated' => ['title' => 'Profile', 'badge_class' => 'bg-info-transparent text-info'],
            'user_created', 'user_updated', 'user_deleted' => ['title' => 'Users', 'badge_class' => 'bg-info-transparent text-info'],
            'auth_failure' => ['title' => 'Auth Failure', 'badge_class' => 'bg-danger-transparent text-danger'],
            default => ['title' => 'Activity', 'badge_class' => 'bg-secondary-transparent text-muted'],
        };
    }
}
