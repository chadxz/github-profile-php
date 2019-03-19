/**
 * Code to enable closing the notification components
 */
document.addEventListener("DOMContentLoaded", () => {
    const $deleteButtons =
        document.querySelectorAll(".notification .delete") || [];

    $deleteButtons.forEach($delete => {
        const $notification = $delete.parentNode;
        $delete.addEventListener("click", () => {
            $notification.parentNode.removeChild($notification);
        });
    });
});
