<?php
/**
 * Buttons Library: Showcase all button variants and sizes for reuse
 *
 * Usage: Include this template in a sandbox or reference the markup.
 * This file is not enqueued automatically on any page.
 */
?>

<div class="nbk-container" style="padding:24px">
    <h2>Buttons Library</h2>

    <h3>Primary</h3>
    <p>
        <a class="nb-btn nb-btn--primary nb-btn--sm" href="#">Primary Small</a>
        <a class="nb-btn nb-btn--primary" href="#">Primary Default</a>
        <a class="nb-btn nb-btn--primary nb-btn--lg" href="#">Primary Large</a>
        <a class="nb-btn nb-btn--primary nb-btn--xl" href="#">Primary XL</a>
    </p>

    <h3>Secondary</h3>
    <p>
        <a class="nb-btn nb-btn--secondary nb-btn--sm" href="#">Secondary Small</a>
        <a class="nb-btn nb-btn--secondary" href="#">Secondary Default</a>
        <a class="nb-btn nb-btn--secondary nb-btn--lg" href="#">Secondary Large</a>
        <a class="nb-btn nb-btn--secondary nb-btn--xl" href="#">Secondary XL</a>
    </p>

    <h3>Icon Only</h3>
    <p>
        <button class="nb-btn nb-btn--primary nb-btn--icon nb-btn--sm" aria-label="Play">
            <span class="nb-btn__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M8 5v14l11-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </span>
        </button>
        <button class="nb-btn nb-btn--primary nb-btn--icon" aria-label="Play">
            <span class="nb-btn__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M8 5v14l11-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </span>
        </button>
        <button class="nb-btn nb-btn--primary nb-btn--icon nb-btn--lg" aria-label="Play">
            <span class="nb-btn__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M8 5v14l11-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </span>
        </button>
        <button class="nb-btn nb-btn--primary nb-btn--icon nb-btn--xl" aria-label="Play">
            <span class="nb-btn__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M8 5v14l11-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </span>
        </button>
    </p>

    <h3>Icon + Subtitle</h3>
    <p>
        <a class="nb-btn nb-btn--secondary" href="#">
            <span class="nb-btn__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M12 6v12m6-6H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="nb-btn__content">
                <span class="nb-btn__label">Create</span>
                <span class="nb-btn__subtitle">New project</span>
            </span>
        </a>
        <a class="nb-btn nb-btn--primary nb-btn--lg" href="#">
            <span class="nb-btn__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span class="nb-btn__content">
                <span class="nb-btn__label">Schedule</span>
                <span class="nb-btn__subtitle">Book a demo</span>
            </span>
        </a>
    </p>

    <h3>Dark Surface Preview</h3>
    <div data-theme="dark" style="background:#0b1220; padding:16px; border-radius:12px;">
        <a class="nb-btn nb-btn--primary" href="#">Primary</a>
        <a class="nb-btn nb-btn--secondary" href="#">Secondary</a>
        <button class="nb-btn nb-btn--secondary nb-btn--icon" aria-label="Menu">
            <span class="nb-btn__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
        </button>
    </div>
</div>


