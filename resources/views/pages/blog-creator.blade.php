@extends('layouts.app')

@section('title', 'SEO Blog Creator')
@section('page_title', 'SEO Blog Creator')
@section('page_badge', 'Primary Workspace')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-12 gap-6 min-h-[calc(100vh-8.5rem)]">
    <!-- Left Column: Article Parameters Form (5 Cols on XL) -->
    <div class="xl:col-span-5 flex flex-col gap-4">
        <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl p-4 sm:p-5">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-base-300">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold">
                        <i data-lucide="pen-tool" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-base-content">Article Configuration</h2>
                        <p class="text-[11px] text-base-content/60">Configure tone, POV, keywords, and AI parameters</p>
                    </div>
                </div>
                <span id="client-badge" class="badge badge-sm badge-ghost font-mono">
                    {{ $activeClient ? $activeClient->name : 'Generic Mode' }}
                </span>
            </div>

            <form id="blog-generator-form" class="space-y-3.5">
                @csrf
                <!-- 1. Client Profile Selection -->
                <div>
                    <label class="label py-0.5 text-xs font-semibold text-base-content/80 flex justify-between">
                        <span>Agency Client Context</span>
                        <a href="{{ route('clients.index') }}" class="text-[11px] text-primary hover:underline">+ New Client</a>
                    </label>
                    <select id="client_id" name="client_id" class="select select-bordered select-sm w-full bg-base-200/50 text-xs">
                        <option value="none" {{ !$activeClient ? 'selected' : '' }}>None (Generic / Independent Mode)</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ $activeClient && $activeClient->id === $c->id ? 'selected' : '' }}
                                data-industry="{{ $c->industry }}"
                                data-tone="{{ $c->brand_tone }}"
                                data-audience="{{ $c->target_audience }}"
                                data-cta="{{ $c->cta_default }}"
                                data-has-wp="{{ !empty($c->wordpress_url) ? '1' : '0' }}">
                                {{ $c->name }} ({{ $c->industry ?: 'Client' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Primary Topic -->
                <div>
                    <label class="label py-0.5 text-xs font-semibold text-base-content/80">Primary Article Topic <span class="text-error">*</span></label>
                    <input type="text" id="topic" name="topic" required
                           placeholder="e.g. Modern Minimalist Interior Design Trends 2026"
                           class="input input-bordered input-sm w-full bg-base-200/50 text-xs text-base-content focus:input-primary" />
                </div>

                <!-- 3. Primary & Secondary Keywords -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div>
                        <label class="label py-0.5 text-xs font-semibold text-base-content/80">Focus Keyword <span class="text-error">*</span></label>
                        <input type="text" id="primary_keyword" name="primary_keyword" required
                               placeholder="e.g. minimalist interior design"
                               class="input input-bordered input-sm w-full bg-base-200/50 text-xs text-base-content" />
                    </div>
                    <div>
                        <label class="label py-0.5 text-xs font-semibold text-base-content/80">Search Intent</label>
                        <select id="search_intent" name="search_intent" class="select select-bordered select-sm w-full bg-base-200/50 text-xs">
                            <option value="Informational" selected>Informational (Learn)</option>
                            <option value="Commercial">Commercial (Investigate)</option>
                            <option value="Transactional">Transactional (Buy)</option>
                            <option value="Navigational">Navigational (Find)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label py-0.5 text-xs font-semibold text-base-content/80">Secondary Keywords (comma separated)</label>
                    <input type="text" id="secondary_keywords" name="secondary_keywords"
                           placeholder="e.g. modern decor, aesthetic room ideas, sustainable furniture"
                           class="input input-bordered input-sm w-full bg-base-200/50 text-xs text-base-content" />
                </div>

                <!-- 4. Industry, Target Audience & Point of View (POV) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <div>
                        <label class="label py-0.5 text-xs font-semibold text-base-content/80">Industry / Domain</label>
                        <input type="text" id="industry" name="industry"
                               value="{{ $activeClient?->industry ?? 'Home Improvement & Architecture' }}"
                               placeholder="e.g. Healthcare, SaaS, Legal"
                               class="input input-bordered input-sm w-full bg-base-200/50 text-xs text-base-content" />
                    </div>
                    <div>
                        <label class="label py-0.5 text-xs font-semibold text-base-content/80">Target Audience</label>
                        <input type="text" id="target_audience" name="target_audience"
                               value="{{ $activeClient?->target_audience ?? 'General Audience' }}"
                               placeholder="e.g. Enterprise Decision-Makers, Patients"
                               class="input input-bordered input-sm w-full bg-base-200/50 text-xs text-base-content" />
                    </div>
                    <div>
                        <label class="label py-0.5 text-xs font-semibold text-base-content/80">Point of View (POV)</label>
                        <select id="pov" name="pov" class="select select-bordered select-sm w-full bg-base-200/50 text-xs">
                            <option value="Second Person" selected>Second Person (You/Your)</option>
                            <option value="First Person">First Person (We/Our)</option>
                            <option value="Third Person">Third Person (They/It)</option>
                        </select>
                    </div>
                </div>

                <!-- 5. Prompt Blueprint Archetype -->
                <div>
                    <label class="label py-0.5 text-xs font-semibold text-base-content/80">Prompt Blueprint Archetype</label>
                    <select id="prompt_template_id" name="prompt_template_id" class="select select-bordered select-sm w-full bg-base-200/50 text-xs">
                        <option value="">Default AI Directive (Comprehensive Master Prompt)</option>
                        @foreach($promptTemplates as $tmpl)
                            <option value="{{ $tmpl->id }}">{{ $tmpl->archetype_name }} {{ $tmpl->is_system ? '(System)' : '(Custom)' }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 6. Format, Word Count & Tone -->
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="label py-0.5 text-[11px] font-semibold text-base-content/80">Format</label>
                        <select id="format" name="format" class="select select-bordered select-sm w-full bg-base-200/50 text-xs">
                            <option value="Ultimate Guide" selected>Ultimate Guide</option>
                            <option value="Listicle">Listicle</option>
                            <option value="How-To">How-To</option>
                            <option value="Product Comparison">Comparison</option>
                            <option value="Explainer">Explainer</option>
                        </select>
                    </div>
                    <div>
                        <label class="label py-0.5 text-[11px] font-semibold text-base-content/80">Length</label>
                        <select id="word_count" name="word_count" class="select select-bordered select-sm w-full bg-base-200/50 text-xs">
                            <option value="Standard" selected>Standard (~1500w)</option>
                            <option value="Short">Short (~800w)</option>
                            <option value="Long-form">Long-form (~2500w)</option>
                            <option value="In-Depth">In-Depth (~3500w+)</option>
                        </select>
                    </div>
                    <div>
                        <label class="label py-0.5 text-[11px] font-semibold text-base-content/80">Tone</label>
                        <select id="tone" name="tone" class="select select-bordered select-sm w-full bg-base-200/50 text-xs">
                            <option value="Authoritative, Informative, Engaging" selected>Authoritative</option>
                            <option value="Conversational, Friendly">Conversational</option>
                            <option value="Professional, Corporate">Corporate</option>
                            <option value="Persuasive, Commercial">Commercial</option>
                        </select>
                    </div>
                </div>

                <!-- 7. Toggles (Humanizer, SERP, Execution Mode) -->
                <div class="bg-base-200/60 rounded-xl p-3 border border-base-300 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 font-medium">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-primary"></i> AI Humanizer Smoothing
                        </span>
                        <input type="checkbox" id="humanizer_active" name="humanizer_active" value="1" checked class="toggle toggle-primary toggle-xs" />
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-1.5 font-medium">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-amber-500"></i> SERP Competitor Scrape
                        </span>
                        <input type="checkbox" id="enable_serp_crawler" name="enable_serp_crawler" value="1" class="toggle toggle-warning toggle-xs" />
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-base-300/50">
                        <span class="flex items-center gap-1.5 font-medium">
                            <i data-lucide="layers" class="w-3.5 h-3.5 text-indigo-400"></i> Multi-Pass Parallel Writing
                        </span>
                        <input type="checkbox" id="multi_pass_mode" name="multi_pass_mode" value="1" checked class="toggle toggle-secondary toggle-xs" />
                    </div>
                </div>

                <!-- 8. Action Buttons -->
                <div class="flex items-center gap-2 pt-2">
                    <button type="button" id="btn-preview-prompt" onclick="openPromptInspector()"
                            class="btn btn-sm btn-outline border-base-300 hover:border-primary flex-1 gap-1 text-xs">
                        <i data-lucide="eye" class="w-3.5 h-3.5 text-primary"></i>
                        <span>Inspect Prompt</span>
                    </button>
                    <button type="button" id="btn-generate-article" onclick="startArticleGeneration(this)"
                            class="btn btn-sm btn-primary flex-1 gap-1 text-xs font-bold shadow-xs">
                        <i data-lucide="play" class="w-3.5 h-3.5 fill-current"></i>
                        <span id="generate-btn-text">Generate Article</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Telemetry Console -->
        <div class="card bg-neutral text-neutral-content border border-base-300 shadow-sm rounded-2xl p-4 font-mono text-xs flex-1 flex flex-col min-h-48 max-h-72">
            <div class="flex items-center justify-between pb-2 mb-2 border-b border-neutral-content/10">
                <span class="flex items-center gap-2 font-bold text-[11px] text-neutral-content/80">
                    <span id="console-pulse" class="w-2 h-2 rounded-full bg-emerald-400"></span> Live Telemetry Terminal
                </span>
                <button type="button" onclick="$('#console-logs').empty()" class="text-[10px] text-neutral-content/60 hover:text-neutral-content">Clear</button>
            </div>
            <div id="console-logs" class="flex-1 overflow-y-auto space-y-1 text-[11px] leading-relaxed pr-1">
                <div class="text-neutral-content/50">[Ready] Engine initialized. Select options and click Generate Article.</div>
            </div>
        </div>
    </div>

    <!-- Right Column: Live Article Workbench (7 Cols on XL) -->
    <div class="xl:col-span-7 flex flex-col gap-4">
        <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl flex-1 flex flex-col overflow-hidden">
            <!-- Article Header & KPI Metrics Bar -->
            <div class="p-4 sm:p-5 border-b border-base-300 bg-base-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 id="workbench-article-title" class="text-base font-bold text-base-content leading-snug">
                        Ready for Article Generation
                    </h1>
                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                        <span class="badge badge-sm badge-ghost font-mono" id="wb-word-count">0 words</span>
                        <span class="badge badge-sm badge-success badge-outline font-mono" id="wb-seo-score">SEO: --</span>
                        <span class="badge badge-sm badge-info badge-outline font-mono" id="wb-flesch-score">Reading Ease: --</span>
                        <span class="badge badge-sm badge-neutral font-mono" id="wb-cost-usd">$0.0000</span>
                        <span class="badge badge-sm badge-ghost font-mono" id="wb-cost-inr">₹0.00</span>
                    </div>
                </div>

                <!-- Export & Publishing Action Buttons -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button" id="btn-download-html" disabled onclick="downloadCurrentArticle('html')"
                            class="btn btn-xs btn-outline border-base-300 gap-1" title="Download HTML File">
                        <i data-lucide="code" class="w-3 h-3"></i> HTML
                    </button>
                    <button type="button" id="btn-download-docx" disabled onclick="downloadCurrentArticle('docx')"
                            class="btn btn-xs btn-outline border-base-300 gap-1" title="Download Word Document">
                        <i data-lucide="file-text" class="w-3 h-3"></i> DOCX
                    </button>
                    <button type="button" id="btn-publish-wp" disabled onclick="publishCurrentArticleToWP(this)"
                            class="btn btn-xs btn-primary gap-1 font-semibold" title="Publish to Client's WordPress">
                        <i data-lucide="share-2" class="w-3 h-3"></i> Publish WP
                    </button>
                </div>
            </div>

            <!-- View Tabs (Rendered Preview, HTML Code, SEO Audit) -->
            <div class="tabs tabs-bordered bg-base-200/50 px-4 border-b border-base-300 text-xs">
                <button type="button" class="tab tab-active wb-tab" data-target="tab-rendered">
                    <i data-lucide="layout" class="w-3.5 h-3.5 mr-1.5"></i> Formatted Preview
                </button>
                <button type="button" class="tab wb-tab" data-target="tab-source">
                    <i data-lucide="code-2" class="w-3.5 h-3.5 mr-1.5"></i> Raw HTML Code
                </button>
                <button type="button" class="tab wb-tab" data-target="tab-metadata">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 mr-1.5"></i> Schema & Metadata
                </button>
            </div>

            <!-- Tab Panels Content Area -->
            <div class="p-5 flex-1 overflow-y-auto max-h-[70vh] bg-base-100">
                <!-- 1. Formatted HTML Preview -->
                <div id="tab-rendered" class="wb-panel prose max-w-none text-base-content leading-relaxed">
                    <div id="article-rendered-content" class="text-base-content/60 text-sm italic py-12 text-center">
                        <i data-lucide="pen-line" class="w-10 h-10 mx-auto mb-3 opacity-30"></i>
                        Generated article content with structured headings, key takeaways, comparison tables, and FAQ schema will render here.
                    </div>
                </div>

                <!-- 2. Raw HTML Source Code -->
                <div id="tab-source" class="wb-panel hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-mono text-base-content/60">Clean HTML Markup</span>
                        <button type="button" onclick="copyRawHtml()" class="btn btn-xs btn-ghost border border-base-300 gap-1">
                            <i data-lucide="copy" class="w-3 h-3"></i> Copy HTML
                        </button>
                    </div>
                    <pre class="bg-base-200/80 p-4 rounded-xl text-xs font-mono overflow-x-auto text-base-content max-h-96"><code id="article-raw-html"></code></pre>
                </div>

                <!-- 3. Schema & Metadata -->
                <div id="tab-metadata" class="wb-panel hidden space-y-4">
                    <div class="bg-base-200/50 p-4 rounded-xl border border-base-300">
                        <h4 class="text-xs font-bold text-base-content uppercase tracking-wider mb-2">Meta Tags</h4>
                        <div class="space-y-1.5 text-xs font-mono">
                            <div><strong class="text-primary">Meta Title:</strong> <span id="meta-title-display" class="text-base-content"></span></div>
                            <div><strong class="text-primary">Meta Description:</strong> <span id="meta-desc-display" class="text-base-content"></span></div>
                            <div><strong class="text-primary">Slug:</strong> <span id="meta-slug-display" class="text-base-content"></span></div>
                        </div>
                    </div>

                    <div class="bg-base-200/50 p-4 rounded-xl border border-base-300">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-bold text-base-content uppercase tracking-wider">JSON-LD Schema Markup</h4>
                            <button type="button" onclick="copySchemaJson()" class="btn btn-xs btn-ghost border border-base-300 gap-1">
                                <i data-lucide="copy" class="w-3 h-3"></i> Copy Schema
                            </button>
                        </div>
                        <pre class="bg-base-300/50 p-3 rounded-lg text-xs font-mono overflow-x-auto text-base-content"><code id="article-schema-json"></code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Complete Prompt Inspector Modal (DaisyUI 5 Modal Standard) -->
<!-- ========================================================================= -->
<dialog id="prompt_inspector_modal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box w-11/12 max-w-5xl bg-base-100 border border-base-300 text-base-content p-0 shadow-2xl overflow-hidden max-h-[92vh] flex flex-col rounded-2xl">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-base-300 bg-base-200/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
                    <i data-lucide="code" class="w-5 h-5"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-bold text-sm text-base-content">Complete LLM Prompt Inspector</h3>
                        <span id="prompt-model-badge" class="badge badge-sm badge-outline badge-primary font-mono">gemini-2.0-flash</span>
                        <span id="prompt-mode-badge" class="badge badge-sm badge-ghost font-mono">Multi-Pass</span>
                        <span id="prompt-pov-badge" class="badge badge-sm badge-neutral font-mono">Second Person</span>
                    </div>
                    <p class="text-[11px] text-base-content/60 mt-0.5">100% unredacted system directives and user prompts dispatched to the AI engine.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="openPromptInspector()" class="btn btn-xs btn-ghost gap-1 border border-base-300">
                    <i data-lucide="refresh-cw" class="w-3 h-3"></i> Refresh
                </button>
                <form method="dialog">
                    <button class="btn btn-xs btn-circle btn-ghost">✕</button>
                </form>
            </div>
        </div>

        <!-- Tabs Header -->
        <div class="tabs tabs-border bg-base-200/30 px-6 border-b border-base-300 text-xs flex items-center gap-1">
            <button type="button" onclick="switchPromptTab('tab-p-master', this)" class="tab prompt-tab tab-active font-semibold py-2.5 transition-all cursor-pointer border-b-2 border-primary text-primary" data-target="tab-p-master">
                <i data-lucide="sparkles" class="w-3.5 h-3.5 mr-1.5"></i> Master Article Prompt
            </button>
            <button type="button" onclick="switchPromptTab('tab-p-outline', this)" class="tab prompt-tab font-semibold py-2.5 transition-all cursor-pointer text-base-content/70 hover:text-base-content" data-target="tab-p-outline">
                <i data-lucide="layers" class="w-3.5 h-3.5 mr-1.5"></i> Pass 1: Outline Architecture
            </button>
            <button type="button" onclick="switchPromptTab('tab-p-sections', this)" class="tab prompt-tab font-semibold py-2.5 transition-all cursor-pointer text-base-content/70 hover:text-base-content" data-target="tab-p-sections">
                <i data-lucide="code" class="w-3.5 h-3.5 mr-1.5"></i> Pass 2: Parallel Section Writing
            </button>
            <button type="button" onclick="switchPromptTab('tab-p-metadata', this)" class="tab prompt-tab font-semibold py-2.5 transition-all cursor-pointer text-base-content/70 hover:text-base-content" data-target="tab-p-metadata">
                <i data-lucide="file-text" class="w-3.5 h-3.5 mr-1.5"></i> Pass 3: Metadata Synthesis
            </button>
        </div>

        <!-- Section Sub-Tabs (Shown when Pass 2 is active) -->
        <div id="section-sub-tabs" class="hidden items-center gap-1.5 px-6 py-2 bg-base-200/80 border-b border-base-300 overflow-x-auto text-xs">
            <span class="text-[11px] text-base-content/60 mr-2 shrink-0">Section:</span>
            <button type="button" onclick="switchSectionPrompt('intro', this)" class="btn btn-xs btn-primary sec-tab">Intro</button>
            <button type="button" onclick="switchSectionPrompt('key-takeaways', this)" class="btn btn-xs btn-ghost sec-tab">Key Takeaways</button>
            <button type="button" onclick="switchSectionPrompt('standard', this)" class="btn btn-xs btn-ghost sec-tab">Standard Body</button>
            <button type="button" onclick="switchSectionPrompt('comparison-table', this)" class="btn btn-xs btn-ghost sec-tab">Comparison Table</button>
            <button type="button" onclick="switchSectionPrompt('faq', this)" class="btn btn-xs btn-ghost sec-tab">FAQ</button>
            <button type="button" onclick="switchSectionPrompt('conclusion', this)" class="btn btn-xs btn-ghost sec-tab">Conclusion</button>
            <button type="button" onclick="switchSectionPrompt('cta', this)" class="btn btn-xs btn-ghost sec-tab">Client CTA</button>
        </div>

        <!-- Tab Content & Code Previews -->
        <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-base-100">
            <!-- Stats & Copy Action Bar -->
            <div class="flex items-center justify-between p-3 bg-base-200/60 border border-base-300 rounded-xl">
                <div id="prompt-stats" class="text-xs text-base-content/70 font-mono">Master Prompt: Complete Single-Pass Directives</div>
                <div class="flex gap-2">
                    <button type="button" onclick="copyElementText('pre-sys-prompt')" class="btn btn-xs btn-ghost border border-base-300">Copy System</button>
                    <button type="button" onclick="copyElementText('pre-user-prompt')" class="btn btn-xs btn-ghost border border-base-300">Copy User</button>
                    <button type="button" onclick="copyCompletePrompt()" class="btn btn-xs btn-primary font-bold">Copy Complete</button>
                </div>
            </div>

            <!-- System Prompt Card -->
            <div class="card bg-base-200/40 border border-base-300 shadow-xs">
                <div class="card-body p-4">
                    <h4 class="text-xs font-mono font-bold text-emerald-500 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> <span id="sys-prompt-header-title">System Directives & Editorial Rules</span>
                    </h4>
                    <pre id="pre-sys-prompt" class="text-xs font-mono text-base-content/90 whitespace-pre-wrap leading-relaxed max-h-60 overflow-y-auto"></pre>
                </div>
            </div>

            <!-- User Prompt Card -->
            <div class="card bg-base-200/40 border border-base-300 shadow-xs">
                <div class="card-body p-4">
                    <h4 class="text-xs font-mono font-bold text-indigo-500 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span> <span id="user-prompt-header-title">Compiled User Prompt Context</span>
                    </h4>
                    <pre id="pre-user-prompt" class="text-xs font-mono text-base-content/90 whitespace-pre-wrap leading-relaxed max-h-60 overflow-y-auto"></pre>
                </div>
            </div>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    let currentArticleId = null;
    let pollInterval = null;
    let cachedPromptData = null;
    let activePromptTab = 'tab-p-master';

    // Tabs Switcher for Workbench
    $(document).on('click', '.wb-tab', function() {
        $('.wb-tab').removeClass('tab-active');
        $(this).addClass('tab-active');
        $('.wb-panel').addClass('hidden');
        $('#' + $(this).data('target')).removeClass('hidden');
    });

    // Tabs Switcher for Prompt Inspector
    function switchPromptTab(targetTab, tabElem) {
        activePromptTab = targetTab;

        // Reset tab styles
        $('.prompt-tab')
            .removeClass('tab-active border-b-2 border-primary text-primary font-bold')
            .addClass('text-base-content/70');
        
        // Highlight active tab
        if (tabElem) {
            $(tabElem)
                .removeClass('text-base-content/70')
                .addClass('tab-active border-b-2 border-primary text-primary font-bold');
        } else {
            $(`.prompt-tab[data-target="${targetTab}"]`)
                .removeClass('text-base-content/70')
                .addClass('tab-active border-b-2 border-primary text-primary font-bold');
        }

        // Section sub-tabs visibility
        if (activePromptTab === 'tab-p-sections') {
            $('#section-sub-tabs').removeClass('hidden').addClass('flex');
        } else {
            $('#section-sub-tabs').addClass('hidden').removeClass('flex');
        }

        if (!cachedPromptData) {
            openPromptInspector();
            return;
        }

        renderActivePromptView();
    }

    // Auto-fill Client Context on Change
    $('#client_id').on('change', function() {
        const selected = $(this).find(':selected');
        if (selected.val() !== 'none') {
            $('#industry').val(selected.data('industry') || $('#industry').val());
            $('#target_audience').val(selected.data('audience') || 'General Audience');
            $('#client-badge').text(selected.text().split('(')[0].trim());
        } else {
            $('#target_audience').val('General Audience');
            $('#client-badge').text('Generic Mode');
        }
    });

    // Open & Load Prompt Inspector
    function openPromptInspector() {
        const formData = getFormData();
        if (!formData.topic) {
            formData.topic = 'Mastering SEO Architecture & High-Performance Copywriting';
        }
        if (!formData.primary_keyword) {
            formData.primary_keyword = 'SEO architecture';
        }

        $('#pre-sys-prompt').text('Compiling live unredacted prompt instructions...');
        $('#pre-user-prompt').text('Loading context...');
        document.getElementById('prompt_inspector_modal').showModal();

        $.ajax({
            url: "{{ route('blog.preview.prompt') }}",
            type: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                cachedPromptData = res;
                $('#prompt-model-badge').text(res.model || res.metadata?.model || 'gemini-2.0-flash');
                $('#prompt-mode-badge').text(formData.multi_pass_mode ? 'Multi-Pass Parallel' : 'Single-Pass');
                $('#prompt-pov-badge').text(formData.pov || 'Second Person');
                renderActivePromptView();
            },
            error: function(xhr) {
                $('#pre-sys-prompt').text('Error generating prompt preview: ' + (xhr.responseJSON?.message || 'Server error'));
                $('#pre-user-prompt').text('');
            }
        });
    }

    function renderActivePromptView() {
        if (!cachedPromptData) return;

        if (activePromptTab === 'tab-p-master') {
            const sys = cachedPromptData.system_prompt 
                || cachedPromptData.master_prompt?.system 
                || cachedPromptData.passes?.master?.system_prompt 
                || 'No master system directives found.';
            const usr = cachedPromptData.user_prompt 
                || cachedPromptData.master_prompt?.user 
                || cachedPromptData.passes?.master?.user_prompt 
                || 'No master user prompt found.';
            $('#sys-prompt-header-title').text('System Directives & Editorial Rules');
            $('#user-prompt-header-title').text('Compiled User Prompt Context');
            $('#prompt-stats').text('Master Prompt: Complete Single-Pass Directives');
            $('#pre-sys-prompt').text(sys);
            $('#pre-user-prompt').text(usr);
        } else if (activePromptTab === 'tab-p-outline') {
            const sys = cachedPromptData.passes?.outline?.system_prompt 
                || cachedPromptData.outline_prompt?.system 
                || cachedPromptData.system_prompt 
                || 'No outline system directives found.';
            const usr = cachedPromptData.passes?.outline?.user_prompt 
                || cachedPromptData.outline_prompt?.user 
                || 'No outline user prompt found.';
            $('#sys-prompt-header-title').text('Pass 1: Outline Engine System Directives');
            $('#user-prompt-header-title').text('Pass 1: Outline Architecture & Competitor Directives');
            $('#prompt-stats').text('Pass 1: JSON Outline Architecture Engine');
            $('#pre-sys-prompt').text(sys);
            $('#pre-user-prompt').text(usr);
        } else if (activePromptTab === 'tab-p-sections') {
            switchSectionPrompt('intro');
        } else if (activePromptTab === 'tab-p-metadata') {
            const sys = cachedPromptData.passes?.metadata?.system_prompt 
                || cachedPromptData.metadata_prompt?.system 
                || cachedPromptData.system_prompt 
                || 'No metadata system directives found.';
            const usr = cachedPromptData.passes?.metadata?.user_prompt 
                || cachedPromptData.metadata_prompt?.user 
                || 'No metadata user prompt found.';
            $('#sys-prompt-header-title').text('Pass 3: Metadata Synthesis System Directives');
            $('#user-prompt-header-title').text('Pass 3: SEO Title, Meta Description & Schema Prompt');
            $('#prompt-stats').text('Pass 3: Metadata & JSON-LD Schema Synthesizer');
            $('#pre-sys-prompt').text(sys);
            $('#pre-user-prompt').text(usr);
        }
    }

    function switchSectionPrompt(secType, btnElem) {
        if (btnElem) {
            $('.sec-tab').removeClass('btn-primary').addClass('btn-ghost');
            $(btnElem).removeClass('btn-ghost').addClass('btn-primary');
        } else {
            $('.sec-tab').removeClass('btn-primary').addClass('btn-ghost');
            $('.sec-tab').filter(function() {
                return ($(this).attr('onclick') || '').indexOf("'" + secType + "'") !== -1;
            }).removeClass('btn-ghost').addClass('btn-primary');
        }

        if (cachedPromptData) {
            const secData = cachedPromptData.passes?.sections?.[secType] 
                || cachedPromptData.section_prompts?.[secType] 
                || {};
            const sys = secData.system_prompt || secData.system || cachedPromptData.system_prompt || 'No section system directives found.';
            const usr = secData.user_prompt || secData.user || ('Directives for section: ' + secType);
            
            const label = secType.replace(/-/g, ' ').toUpperCase();
            $('#sys-prompt-header-title').text('Pass 2: [' + label + '] Section System Directives');
            $('#user-prompt-header-title').text('Pass 2: [' + label + '] Section Writing Context');
            $('#prompt-stats').text('Pass 2: Parallel Section Copywriter (' + label + ')');
            $('#pre-sys-prompt').text(sys);
            $('#pre-user-prompt').text(usr);
        }
    }

    function copyElementText(elemId) {
        const text = document.getElementById(elemId).innerText;
        navigator.clipboard.writeText(text);
        showToast('Copied to clipboard!', 'success');
    }

    function copyCompletePrompt() {
        const sys = document.getElementById('pre-sys-prompt').innerText;
        const user = document.getElementById('pre-user-prompt').innerText;
        navigator.clipboard.writeText(sys + "\n\n=== USER PROMPT ===\n\n" + user);
        showToast('Complete prompt copied!', 'success');
    }

    function copyRawHtml() {
        const code = document.getElementById('article-raw-html').innerText;
        navigator.clipboard.writeText(code);
        showToast('HTML code copied!', 'success');
    }

    function copySchemaJson() {
        const code = document.getElementById('article-schema-json').innerText;
        navigator.clipboard.writeText(code);
        showToast('JSON-LD Schema copied!', 'success');
    }

    function getFormData() {
        const formArray = $('#blog-generator-form').serializeArray();
        const data = {};
        formArray.forEach(item => {
            data[item.name] = item.value;
        });
        data.humanizer_active = $('#humanizer_active').is(':checked') ? 1 : 0;
        data.enable_serp_crawler = $('#enable_serp_crawler').is(':checked') ? 1 : 0;
        data.multi_pass_mode = $('#multi_pass_mode').is(':checked') ? 1 : 0;
        return data;
    }

    // Start Generation Execution
    function startArticleGeneration(btn) {
        const formData = getFormData();
        if (!formData.topic || !formData.primary_keyword) {
            showToast('Please provide both Topic and Focus Keyword.', 'warning');
            return;
        }

        $(btn).attr('disabled', 'disabled').addClass('opacity-75');
        $('#generate-btn-text').html('<span class="loading loading-spinner loading-xs me-1"></span> Generating...');
        $('#console-logs').html('<div class="text-primary font-bold">[' + new Date().toLocaleTimeString() + '] Dispatching live article generation job...</div>');

        $.ajax({
            url: "{{ route('blog.generate') }}",
            type: 'POST',
            data: formData,
            success: function(res) {
                const jobId = res.job_id;
                logConsole('Job #' + jobId + ' dispatched successfully. Polling live telemetry...');
                startTelemetryPolling(jobId);
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').removeClass('opacity-75');
                $('#generate-btn-text').text('Generate Article');
                const err = xhr.responseJSON?.message || 'Generation failed. Check API key configuration in Settings.';
                logConsole('[Error] ' + err, 'text-error');
                showToast(err, 'error');
            }
        });
    }

    function startTelemetryPolling(jobId) {
        if (pollInterval) clearInterval(pollInterval);

        pollInterval = setInterval(() => {
            $.ajax({
                url: "/blog-creator/jobs/" + jobId + "/logs",
                type: 'GET',
                success: function(res) {
                    if (res.logs && res.logs.length) {
                        $('#console-logs').empty();
                        res.logs.forEach(l => {
                            logConsole(l);
                        });
                    }

                    if (res.metrics) {
                        $('#wb-word-count').text((res.metrics.words_generated || 0) + ' words');
                        $('#wb-seo-score').text('SEO: ' + (res.metrics.seo_score || '--'));
                        $('#wb-flesch-score').text('Reading Ease: ' + (res.metrics.flesch_reading_ease || '--'));
                        const usd = res.metrics.current_cost_usd || 0;
                        $('#wb-cost-usd').text('$' + usd.toFixed(4));
                        $('#wb-cost-inr').text('₹' + (usd * 84.0).toFixed(2));
                    }

                    if (res.status === 'completed') {
                        clearInterval(pollInterval);
                        $('#btn-generate-article').removeAttr('disabled').removeClass('opacity-75');
                        $('#generate-btn-text').text('Generate Article');
                        showToast('Article generated successfully!', 'success');
                        loadCompletedArticle(jobId);
                    } else if (res.status === 'failed') {
                        clearInterval(pollInterval);
                        $('#btn-generate-article').removeAttr('disabled').removeClass('opacity-75');
                        $('#generate-btn-text').text('Generate Article');
                        logConsole('[Fatal] Generation failed.', 'text-error');
                        showToast('Generation failed. Check console log for details.', 'error');
                    }
                }
            });
        }, 2000);
    }

    function logConsole(msg, extraClass = 'text-neutral-content/80') {
        const time = new Date().toLocaleTimeString();
        $('#console-logs').append('<div class="' + extraClass + '">[' + time + '] ' + msg + '</div>');
        const box = document.getElementById('console-logs');
        box.scrollTop = box.scrollHeight;
    }

    function loadCompletedArticle(jobId) {
        $.ajax({
            url: "/api/seo-generation/jobs/" + jobId,
            type: 'GET',
            success: function(job) {
                // Fetch the article tied to this job
                $.ajax({
                    url: "/api/articles",
                    type: 'GET',
                    success: function(articles) {
                        const target = (articles.data || articles).find(a => a.seo_generation_job_id === job.id) || (articles.data || articles)[0];
                        if (target) {
                            currentArticleId = target.id;
                            $('#workbench-article-title').text(target.title);
                            $('#article-rendered-content').html(target.html_content);
                            $('#article-raw-html').text(target.html_content);
                            $('#meta-title-display').text(target.meta_title || target.title);
                            $('#meta-desc-display').text(target.meta_description || 'N/A');
                            $('#meta-slug-display').text(target.slug);
                            $('#article-schema-json').text(JSON.stringify(target.schema_jsonld || target.schema_json || {}, null, 2));

                            $('#btn-download-html').removeAttr('disabled');
                            $('#btn-download-docx').removeAttr('disabled');
                            $('#btn-publish-wp').removeAttr('disabled');
                            window.refreshIcons();
                        }
                    }
                });
            }
        });
    }

    function downloadCurrentArticle(format) {
        if (!currentArticleId) return;
        window.location.href = "/articles/" + currentArticleId + "/download/" + format;
    }

    function publishCurrentArticleToWP(btn) {
        if (!currentArticleId) return;
        $(btn).attr('disabled', 'disabled');
        const orig = $(btn).html();
        $(btn).html('<span class="loading loading-spinner loading-xs me-1"></span> Publishing...');

        $.ajax({
            url: "/articles/" + currentArticleId + "/publish-wp",
            type: 'POST',
            success: function(res) {
                $(btn).removeAttr('disabled').html(orig);
                showToast(res.message || 'Published to WordPress successfully!', 'success');
            },
            error: function(xhr) {
                $(btn).removeAttr('disabled').html(orig);
                const msg = xhr.responseJSON?.message || 'WordPress publishing failed. Configure credentials in Client settings.';
                showToast(msg, 'error');
            }
        });
    }
</script>
@endpush
