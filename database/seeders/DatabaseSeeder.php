<?php

namespace Database\Seeders;

use App\Models\AiPreset;
use App\Models\ArticleOption;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Permissions
        $managePresets = Permission::firstOrCreate(['name' => 'manage-presets']);
        $generateContent = Permission::firstOrCreate(['name' => 'generate-content']);
        $viewContent = Permission::firstOrCreate(['name' => 'view-content']);

        // 2. Create Roles and Assign Permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->syncPermissions([$managePresets, $generateContent, $viewContent]);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions([$managePresets, $generateContent, $viewContent]);

        $editorRole = Role::firstOrCreate(['name' => 'editor']);
        $editorRole->syncPermissions([$managePresets, $generateContent, $viewContent]);

        $writerRole = Role::firstOrCreate(['name' => 'writer']);
        $writerRole->syncPermissions([$generateContent, $viewContent]);

        $viewerRole = Role::firstOrCreate(['name' => 'viewer']);
        $viewerRole->syncPermissions([$viewContent]);

        // 3. Create Seed Users for testing
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@webaiseo.com'],
            ['name' => 'Master Super Admin', 'password' => bcrypt('password123')]
        );
        $superAdminUser->assignRole($superAdminRole);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => bcrypt('password')]
        );
        $adminUser->assignRole($adminRole);

        $editorUser = User::firstOrCreate(
            ['email' => 'editor@example.com'],
            ['name' => 'Editor User', 'password' => bcrypt('password')]
        );
        $editorUser->assignRole($editorRole);

        $viewerUser = User::firstOrCreate(
            ['email' => 'viewer@example.com'],
            ['name' => 'Viewer User', 'password' => bcrypt('password')]
        );
        $viewerUser->assignRole($viewerRole);

        // Seed Sample Agency Client for immediate testing
        Client::firstOrCreate(
            ['name' => 'GEIMS Hospital'],
            [
                'user_id' => $superAdminUser->id,
                'website_url' => 'https://geimshospital.com/',
                'industry' => 'Healthcare & Medical',
                'brand_tone' => 'Authoritative, Empathetic, Medical Expert',
                'target_audience' => 'Patients, Family Members, and Healthcare Seekers in North India',
                'cta_default' => 'Book an appointment with GEIMS Hospital medical specialists today.',
                'competitor_urls' => ['https://maxhealthcare.in', 'https://fortishealthcare.com'],
                'approved_reference_domains' => ['who.int', 'nih.gov', 'nhp.gov.in'],
                'sitemap_url' => 'https://geimshospital.com/sitemap_index.xml',
                'is_active' => true,
            ]
        );

        // 4. Seed Standard Prompt Strategy Presets matching PROMPTS_AND_ARTICLE_STRUCTURE.md
        $standardPresets = [
            [
                'name' => 'Google E-E-A-T Standard',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
                'max_workers' => 3,
                'temperature' => 0.7,
                'custom_instructions' => 'Focus on Google E-E-A-T standards. Provide deep first-hand experience insights, expert breakdown, actionable steps, and clear bullet points.',
                'is_active' => true,
            ],
            [
                'name' => 'Affiliate Buyer Guide',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
                'max_workers' => 3,
                'temperature' => 0.7,
                'custom_instructions' => 'Focus on commercial intent. Compare features, highlight pros & cons, present clear buyer recommendations, and end with a strong purchasing verdict CTA.',
                'is_active' => false,
            ],
            [
                'name' => 'B2B Whitepaper / Executive',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
                'max_workers' => 3,
                'temperature' => 0.6,
                'custom_instructions' => 'Write in an authoritative, data-backed corporate tone suitable for enterprise executives, software architects, and decision-makers.',
                'is_active' => false,
            ],
            [
                'name' => 'Viral Blog Post / Engaging',
                'provider' => 'gemini',
                'model' => 'gemini-2.0-flash',
                'max_workers' => 3,
                'temperature' => 0.8,
                'custom_instructions' => 'Write in a highly engaging, conversational tone with storytelling hooks, short snappy paragraphs, and interactive sub-headings.',
                'is_active' => false,
            ],
        ];

        foreach ([$adminUser, $editorUser] as $user) {
            foreach ($standardPresets as $presetData) {
                AiPreset::updateOrCreate(
                    ['user_id' => $user->id, 'name' => $presetData['name']],
                    $presetData
                );
            }
        }

        // 5. Seed Article Options (Search Intent, Format, Word Count, Tone, Target Audience, Language)
        $defaultArticleOptions = [
            // Search Intent
            ['category' => 'search_intent', 'label' => 'Informational', 'value' => 'Informational', 'description' => 'Educates users and answers questions', 'is_default' => true, 'sort_order' => 1],
            ['category' => 'search_intent', 'label' => 'Commercial', 'value' => 'Commercial', 'description' => 'Compares products and highlights buyer options', 'is_default' => false, 'sort_order' => 2],
            ['category' => 'search_intent', 'label' => 'Transactional', 'value' => 'Transactional', 'description' => 'Drives immediate action or purchase decisions', 'is_default' => false, 'sort_order' => 3],
            ['category' => 'search_intent', 'label' => 'Navigational', 'value' => 'Navigational', 'description' => 'Directs users to specific brand pages', 'is_default' => false, 'sort_order' => 4],

            // Format
            ['category' => 'format', 'label' => 'Ultimate Guide', 'value' => 'Ultimate Guide', 'description' => 'Comprehensive deep dive covering all aspects', 'is_default' => true, 'sort_order' => 1],
            ['category' => 'format', 'label' => 'Listicle', 'value' => 'Listicle', 'description' => 'Structured list of numbered key items', 'is_default' => false, 'sort_order' => 2],
            ['category' => 'format', 'label' => 'How-To', 'value' => 'How-To', 'description' => 'Step-by-step tutorial with instructions', 'is_default' => false, 'sort_order' => 3],
            ['category' => 'format', 'label' => 'Product Comparison', 'value' => 'Product Comparison', 'description' => 'Side-by-side analysis of features, pros & cons', 'is_default' => false, 'sort_order' => 4],
            ['category' => 'format', 'label' => 'Explainer', 'value' => 'Explainer', 'description' => 'Clear, simple breakdown of complex subjects', 'is_default' => false, 'sort_order' => 5],
            ['category' => 'format', 'label' => 'Case Study', 'value' => 'Case Study', 'description' => 'Real-world analysis of results and implementations', 'is_default' => false, 'sort_order' => 6],

            // Word Count
            ['category' => 'word_count', 'label' => 'Short (600 - 1,000 words)', 'value' => 'Short', 'description' => 'Quick overview', 'is_default' => false, 'sort_order' => 1],
            ['category' => 'word_count', 'label' => 'Standard (1,200 - 1,800 words)', 'value' => 'Standard', 'description' => 'Standard blog post length', 'is_default' => true, 'sort_order' => 2],
            ['category' => 'word_count', 'label' => 'Long-form (2,000 - 3,000 words)', 'value' => 'Long-form', 'description' => 'In-depth comprehensive guide', 'is_default' => false, 'sort_order' => 3],
            ['category' => 'word_count', 'label' => 'In-Depth (3,500+ words)', 'value' => 'In-Depth', 'description' => 'Complete pillar content', 'is_default' => false, 'sort_order' => 4],

            // Tone
            ['category' => 'tone', 'label' => 'Conversational & Engaging', 'value' => 'Conversational', 'description' => 'Engaging, friendly, and easy to read', 'is_default' => true, 'sort_order' => 1],
            ['category' => 'tone', 'label' => 'Professional & Authoritative', 'value' => 'Professional & Authoritative', 'description' => 'Corporate, executive-ready, and expert', 'is_default' => false, 'sort_order' => 2],
            ['category' => 'tone', 'label' => 'Technical & Data-Backed', 'value' => 'Technical & Data-Backed', 'description' => 'Detailed analysis for engineers', 'is_default' => false, 'sort_order' => 3],
            ['category' => 'tone', 'label' => 'Persuasive & Sales-Oriented', 'value' => 'Persuasive & Sales-Oriented', 'description' => 'High-conversion pitch focused on benefits', 'is_default' => false, 'sort_order' => 4],
            ['category' => 'tone', 'label' => 'Friendly & Approachable', 'value' => 'Friendly & Approachable', 'description' => 'Casual, warm, and inviting', 'is_default' => false, 'sort_order' => 5],

            // Target Audience
            ['category' => 'target_audience', 'label' => 'General Audience', 'value' => 'General Audience', 'description' => 'Broad readership looking for clear answers', 'is_default' => true, 'sort_order' => 1],
            ['category' => 'target_audience', 'label' => 'Enterprise Decision-Makers', 'value' => 'Enterprise Decision-Makers', 'description' => 'Executives, directors, and VPs', 'is_default' => false, 'sort_order' => 2],
            ['category' => 'target_audience', 'label' => 'Developers & Architects', 'value' => 'Developers & Architects', 'description' => 'Engineers and technical practitioners', 'is_default' => false, 'sort_order' => 3],
            ['category' => 'target_audience', 'label' => 'Beginners & Students', 'value' => 'Beginners & Students', 'description' => 'Newcomers looking for simple fundamentals', 'is_default' => false, 'sort_order' => 4],
            ['category' => 'target_audience', 'label' => 'Small Business Owners', 'value' => 'Small Business Owners', 'description' => 'Entrepreneurs and operators', 'is_default' => false, 'sort_order' => 5],
            ['category' => 'target_audience', 'label' => 'E-commerce Shoppers', 'value' => 'E-commerce Shoppers', 'description' => 'Consumers comparing products to buy', 'is_default' => false, 'sort_order' => 6],

            // Language
            ['category' => 'language', 'label' => 'English', 'value' => 'English', 'description' => 'Standard English', 'is_default' => true, 'sort_order' => 1],
            ['category' => 'language', 'label' => 'Spanish', 'value' => 'Spanish', 'description' => 'Spanish (Español)', 'is_default' => false, 'sort_order' => 2],
            ['category' => 'language', 'label' => 'French', 'value' => 'French', 'description' => 'French (Français)', 'is_default' => false, 'sort_order' => 3],
            ['category' => 'language', 'label' => 'German', 'value' => 'German', 'description' => 'German (Deutsch)', 'is_default' => false, 'sort_order' => 4],
            ['category' => 'language', 'label' => 'Italian', 'value' => 'Italian', 'description' => 'Italian (Italiano)', 'is_default' => false, 'sort_order' => 5],
            ['category' => 'language', 'label' => 'Portuguese', 'value' => 'Portuguese', 'description' => 'Portuguese (Português)', 'is_default' => false, 'sort_order' => 6],
            ['category' => 'language', 'label' => 'Dutch', 'value' => 'Dutch', 'description' => 'Dutch (Nederlands)', 'is_default' => false, 'sort_order' => 7],
        ];

        foreach ($defaultArticleOptions as $opt) {
            ArticleOption::updateOrCreate(
                ['category' => $opt['category'], 'value' => $opt['value']],
                $opt
            );
        }

        // 6. Seed System Archetype Prompt Blueprints
        $this->call(AiPromptTemplateSeeder::class);
    }
}
