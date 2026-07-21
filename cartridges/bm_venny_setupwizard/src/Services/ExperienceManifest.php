<?php

declare(strict_types=1);

final class ExperienceManifest
{
    public const ALWAYS_MODULES = ['Platform Primitives', 'Personal Information'];

    public static function modules(): array
    {
        return [
            'Platform Primitives' => [
                'label' => 'Platform Primitives',
                'locked' => true,
                'tables' => ['apps', 'keys', 'sessions', 'windows'],
            ],
            'Personal Information' => [
                'label' => 'Personal Information',
                'locked' => true,
                'tables' => ['persons', 'users', 'profiles', 'assets', 'content', 'communications', 'deliveries'],
            ],
            'CRM' => [
                'label' => 'CRM',
                'locked' => false,
                'tables' => ['contacts', 'companies', 'deals', 'pipelines', 'stages', 'activities', 'tasks', 'notes', 'tags'],
            ],
            'Engagement' => [
                'label' => 'Engagement',
                'locked' => false,
                'tables' => ['threads', 'messages', 'posts', 'followships', 'groups', 'acknowledgements', 'comments'],
            ],
            'E-Commerce' => [
                'label' => 'E-Commerce',
                'locked' => false,
                'tables' => ['catalogs', 'categories', 'products', 'items', 'transactions', 'orders', 'coupons', 'customers'],
            ],
        ];
    }

    public static function experiences(): array
    {
        return [
            'base' => [
                'label' => 'Base App',
                'description' => 'Auth, app keys, sessions, files, CMS, people/profile records, communications.',
                'modules' => self::ALWAYS_MODULES,
            ],
            'crm' => [
                'label' => 'CRM App',
                'description' => 'Base app plus contacts, companies, deals, pipelines, tasks, notes, and tags.',
                'modules' => array_merge(self::ALWAYS_MODULES, ['CRM']),
            ],
            'community' => [
                'label' => 'Community App',
                'description' => 'Base app plus threads, messages, posts, groups, comments, follows, and reactions.',
                'modules' => array_merge(self::ALWAYS_MODULES, ['Engagement']),
            ],
            'commerce' => [
                'label' => 'Commerce App',
                'description' => 'Base app plus catalog, categories, products, items, transactions, orders, coupons, and customers.',
                'modules' => array_merge(self::ALWAYS_MODULES, ['E-Commerce']),
            ],
            'full' => [
                'label' => 'Full Platform App',
                'description' => 'Base app plus CRM, engagement, and e-commerce.',
                'modules' => array_merge(self::ALWAYS_MODULES, ['CRM', 'Engagement', 'E-Commerce']),
            ],
            'custom' => [
                'label' => 'Custom',
                'description' => 'Base app plus whichever optional modules you check.',
                'modules' => self::ALWAYS_MODULES,
            ],
        ];
    }

    public static function resolveModules(string $experience, array $optionalModules): array
    {
        $experiences = self::experiences();
        $modules = $experiences[$experience]['modules'] ?? self::ALWAYS_MODULES;
        if ($experience === 'custom') {
            $modules = array_merge($modules, array_values(array_intersect($optionalModules, ['CRM', 'Engagement', 'E-Commerce'])));
        }
        return array_values(array_unique($modules));
    }

    public static function resolveTables(array $modules): array
    {
        $all = self::modules();
        $tables = ['installations', 'steps']; // setup metadata tables are safe to keep installed globally
        foreach ($modules as $module) {
            foreach (($all[$module]['tables'] ?? []) as $table) {
                $tables[] = $table;
            }
        }
        return array_values(array_unique($tables));
    }
}
