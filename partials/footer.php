    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-copy-target]').forEach(function (button) {
    button.addEventListener('click', async function () {
        const targetSelector = button.getAttribute('data-copy-target');
        const target = document.querySelector(targetSelector);

        if (!target) {
            return;
        }

        try {
            await navigator.clipboard.writeText(target.textContent.trim());
            button.textContent = 'Copied';
            setTimeout(function () {
                button.textContent = 'Copy details';
            }, 1500);
        } catch (error) {
            button.textContent = 'Copy failed';
        }
    });
});
</script>
</body>
</html>