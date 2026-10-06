// Logout
document.addEventListener("DOMContentLoaded", function () {
    document
        .getElementById(
            "user-menu-link-logout"
        )
        ?.addEventListener(
            "click",
            logout
        );
});

async function logout() {
    try {
        const response =
            await fetch(
                API.logout,
                {
                    method: "POST"
                }
            );

        const data =
            await readJsonResponse(response);

        if (
            !response.ok ||
            !data.success
        ) {
            alert(
                data.message ??
                "Logout error"
            );
            return;
        }

        currentUser = null;

        document.location.reload();

    } catch (error) {
        console.error(error);

        alert(
            "Unable to connect to the server"
        );
    }
}