{{--
    The session ends after two hours without requests, and a manager page left open that long
    then failed with "419" on the next upload or save. Each open page now checks in every ten
    minutes and when she comes back to the tab. If the session did end (the computer slept,
    for example), she is told and the page reloads into the login, which brings her back here.
--}}
@auth
    <script>
        (() => {
            const keepAliveUrl = @js(route('manager.keep-alive'));
            let lastCheck = Date.now();

            const checkIn = async () => {
                lastCheck = Date.now();

                try {
                    const response = await fetch(keepAliveUrl, { headers: { Accept: 'application/json' } });

                    if (response.status === 401) {
                        alert('Tu sesión se cerró por inactividad. Vuelve a entrar para seguir; lo que no guardaste no se guardó.');
                        window.location.reload();
                    }
                } catch {
                    // Offline for a moment: the next check will try again
                }
            };

            setInterval(checkIn, 10 * 60 * 1000);

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && Date.now() - lastCheck > 60 * 1000) {
                    checkIn();
                }
            });
        })();
    </script>
@endauth
