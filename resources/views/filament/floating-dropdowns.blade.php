{{--
    Filament keeps a select's list inside its window, so in a short window (editing a home
    section) the list was cut off and had to be scrolled inside the window. Right before a
    select opens, its window lets the list float above everything instead; the list still
    follows the field when the window scrolls.
--}}
<script>
    (() => {
        const letListsFloat = (event) => event.target.closest?.('.fi-modal.fi-absolute-positioning-context')
            ?.classList.replace('fi-absolute-positioning-context', 'fi-fixed-positioning-context');

        document.addEventListener('pointerdown', letListsFloat, true);
        document.addEventListener('keydown', letListsFloat, true);
    })();
</script>
