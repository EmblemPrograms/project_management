<?php
/**
 * Show / hide password toggle.
 *
 * Include this once, just before </body>, on any page that renders a
 * <input type="password">. Every password field on the page is wrapped in a
 * Bootstrap input-group and given an eye button automatically — no markup
 * changes are needed on the fields themselves.
 *
 * Fields added to the DOM later can be wired up with PasswordToggle.refresh().
 */
?>
<style>
    .pw-toggle-btn {
        border-color: var(--bs-border-color, #dee2e6);
        background: #fff;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        padding-left: .75rem;
        padding-right: .75rem;
    }
    .pw-toggle-btn:hover,
    .pw-toggle-btn:focus {
        background: #f8f9fa;
        color: #198754;
    }
    .pw-toggle-btn svg { width: 18px; height: 18px; pointer-events: none; }
    .pw-toggle-btn .pw-icon-hide { display: none; }
    .pw-toggle-btn[aria-pressed="true"] .pw-icon-show { display: none; }
    .pw-toggle-btn[aria-pressed="true"] .pw-icon-hide { display: block; }
</style>
<script>
(function () {
    var ICONS =
        '<svg class="pw-icon-show" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"' +
        ' stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
        '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>' +
        '<svg class="pw-icon-hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"' +
        ' stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
        '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>' +
        '<line x1="1" y1="1" x2="23" y2="23"></line></svg>';

    function attach(input) {
        if (input.dataset.pwToggle === 'on') return;
        input.dataset.pwToggle = 'on';

        var group = input.parentElement;
        if (!group || !group.classList.contains('input-group')) {
            group = document.createElement('div');
            group.className = 'input-group';
            input.parentNode.insertBefore(group, input);
            group.appendChild(input);
        }

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn pw-toggle-btn';
        btn.setAttribute('aria-pressed', 'false');
        btn.setAttribute('aria-label', 'Show password');
        btn.setAttribute('title', 'Show password');
        btn.innerHTML = ICONS;

        btn.addEventListener('click', function () {
            var shown = input.type === 'text';
            input.type = shown ? 'password' : 'text';
            btn.setAttribute('aria-pressed', shown ? 'false' : 'true');
            btn.setAttribute('aria-label', shown ? 'Show password' : 'Hide password');
            btn.setAttribute('title', shown ? 'Show password' : 'Hide password');
            input.focus();
        });

        group.appendChild(btn);
    }

    function refresh(root) {
        var scope = root || document;
        var fields = scope.querySelectorAll('input[type="password"]');
        for (var i = 0; i < fields.length; i++) attach(fields[i]);
    }

    window.PasswordToggle = { refresh: refresh };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { refresh(); });
    } else {
        refresh();
    }
})();
</script>
