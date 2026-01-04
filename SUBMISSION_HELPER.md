1.  **Plugin Name:** Flux Cleanup Mate

2.  **Short Description:** A unique PHP-only WordPress utility. A flux style cleanup plugin acting as a mate. Focused on simplicity and efficiency.

3.  **Detailed Description:**
    Flux Cleanup Mate is a lightweight, PHP-only utility designed to streamline and optimize your WordPress site. It acts as a "mate" to help you easily disable various default WordPress features that might not be needed, thereby improving performance, enhancing security, and decluttering the user experience.

    **Key Features:**
    *   **Core Cleanup:** Effortlessly disable WordPress emojis, RSD and WLW manifest links, shortlink tags, generator tags, RSS feed links, REST API discovery links, and oEmbed functionality.
    *   **Script Optimization:** Remove `comment-reply.js` (except on singular posts where needed) and `jquery-migrate` from the frontend, reducing script load.
    *   **Performance & Server Load:** Control the Heartbeat API by disabling it on the frontend for non-logged-in/non-editor users and increasing its interval in the admin area.
    *   **Security Enhancements:** Option to completely disable XML-RPC functionality and remove the X-Pingback header, improving site security.
    *   **Admin Experience Refinement:** Hide the WordPress admin bar on the frontend for all users except administrators.
    *   **Dashboard Management:** Selectively remove common default dashboard widgets (e.g., At a Glance, Activity, Site Health) to simplify the admin interface for yourself and your clients.
    *   **Database Optimization:** Take control of post revisions, setting a maximum number to keep for each post/page or disabling them entirely to keep your database lean.

    Flux Cleanup Mate provides a simple, intuitive settings page under the WordPress 'Settings' menu, allowing you to easily configure all cleanup options via checkboxes and number inputs. It's built with simplicity and efficiency in mind, offering a powerful way to keep your WordPress installation clean and performant without adding unnecessary bloat.

4.  **GitHub URL:** https://github.com/ogichanchan/flux-cleanup-mate