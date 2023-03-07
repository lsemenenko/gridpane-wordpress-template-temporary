<?php

namespace Breakdance\Admin;

use function Breakdance\Subscription\getSubscriptionMode;

add_action('admin_menu', 'Breakdance\Admin\admin_menu');

function admin_menu()
{
    if (\Breakdance\Permissions\hasPermission('full')) {
        // TODO: better to have a declarative way to create menus with permissions,
        // rather than complecting the menu with the permissions
        add_menu_page('Breakdance', 'Breakdance', 'manage_options', 'breakdance', '', 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4gPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2OCIgaGVpZ2h0PSI2OCIgdmlld0JveD0iMCAwIDQ5IDY4IiBmaWxsPSJub25lIj48cGF0aCBkPSJNMjcuODk5NCA2Ni4wMDFDMzEuNzA5OCA2Ni4wMDEgMzQuNzk4OCA2Mi45MTIgMzQuNzk4OCA1OS4xMDE2QzM0Ljc5ODggNTUuMjkxMSAzMS43MDk4IDUyLjIwMjEgMjcuODk5NCA1Mi4yMDIxQzI0LjA4OSA1Mi4yMDIxIDIxIDU1LjI5MTEgMjEgNTkuMTAxNkMyMSA2Mi45MTIgMjQuMDg5IDY2LjAwMSAyNy44OTk0IDY2LjAwMVoiIGZpbGw9ImJsYWNrIj48L3BhdGg+PHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik00LjgzNCA0OC4zNEMzLjU1MTg0IDQ4LjMzOTIgMi4zMjI0NSA0Ny44Mjk0IDEuNDE2MDIgNDYuOTIyNkMwLjUwOTU4MSA0Ni4wMTU4IDAuMDAwMjY0ODI3IDQ0Ljc4NjIgMCA0My41MDRDMC4wMDEwNTk5IDQyLjIyMjIgMC41MTA4NjQgNDAuOTkzMiAxLjQxNzQzIDQwLjA4N0MyLjMyNCAzOS4xODA4IDMuNTUzMTkgMzguNjcxNSA0LjgzNSAzOC42NzFIMTQuNTM1QzE1LjgxMTMgMzguNjYyIDE3LjAzMjQgMzguMTQ4OCAxNy45MzE5IDM3LjI0MzNDMTguODMxNCAzNi4zMzc4IDE5LjMzNjUgMzUuMTEzNCAxOS4zMzcgMzMuODM3VjQuODMzQzE5LjMzNzggMy41NTE0NSAxOS44NDcyIDIuMzIyNjIgMjAuNzUzNCAxLjQxNjQzQzIxLjY1OTYgMC41MTAyNCAyMi44ODg1IDAuMDAwNzk0NzU4IDI0LjE3IDBDMjUuNDUxNyAwLjAwMDUyOTgyOCAyNi42ODA4IDAuNTA5ODU4IDI3LjU4NzIgMS40MTYwOEMyOC40OTM2IDIuMzIyMyAyOS4wMDMyIDMuNTUxMjggMjkuMDA0IDQuODMzVjE4LjUwM0MyOS4wMDQ4IDE5Ljc4NDUgMzAuNDIwNCAyMS45MTk2IDMwLjQyMDQgMjEuOTE5NkMzMS4zMjY2IDIyLjgyNTggMzIuNTU1NSAyMy4zMzUyIDMzLjgzNyAyMy4zMzZDMzUuMTE4OSAyMy4zMzU1IDM2LjM0ODEgMjIuODI2IDM3LjI1NDYgMjEuOTE5NkMzOC4xNjEgMjEuMDEzMSAzOC42NzA1IDE5Ljc4MzkgMzguNjcxIDE4LjUwMlY0LjgzM0MzOC42NzE4IDMuNTUxMjggMzkuMTgxNCAyLjMyMjMgNDAuMDg3OCAxLjQxNjA4QzQwLjk5NDIgMC41MDk4NTggNDIuMjIzMyAwLjAwMDUyOTgyOCA0My41MDUgMEM0NC43ODY4IDAuMDAwNTI5NTY1IDQ2LjAxNiAwLjUwOTgyNSA0Ni45MjI2IDEuNDE2MDJDNDcuODI5MSAyLjMyMjIxIDQ4LjMzODkgMy41NTExOSA0OC4zNCA0LjgzM1Y2Mi44NDJDNDguMzM5MiA2NC4xMjQgNDcuODI5NSA2NS4zNTMyIDQ2LjkyMjkgNjYuMjU5NkM0Ni4wMTYzIDY3LjE2NiA0NC43ODcgNjcuNjc1NSA0My41MDUgNjcuNjc2QzQyLjIyMzEgNjcuNjc1NSA0MC45OTM5IDY3LjE2NiA0MC4wODc0IDY2LjI1OTZDMzkuMTgxIDY1LjM1MzEgMzguNjcxNSA2NC4xMjM5IDM4LjY3MSA2Mi44NDJWNTguMDA4QzM4LjY3MSA1Mi42NzEgMzQuMzM5IDQ4LjMzOSAyOS4wMDQgNDguMzM5TDQuODM0IDQ4LjM0WiIgZmlsbD0iYmxhY2siPjwvcGF0aD48L3N2Zz4g');
        add_submenu_page('breakdance', 'Home', 'Home', 'manage_options', 'breakdance', 'Breakdance\Admin\home');
        /** @psalm-suppress UndefinedFunction  */
        add_submenu_page('breakdance', 'Templates', 'Templates', 'manage_options', BREAKDANCE_TEMPLATE_POST_TYPE, "Breakdance\Themeless\ManageTemplates\getManageBreakdancePostTypesSpaHtml");
        /** @psalm-suppress UndefinedFunction  */
        add_submenu_page('breakdance', 'Headers', 'Headers', 'manage_options', BREAKDANCE_HEADER_POST_TYPE, "Breakdance\Themeless\ManageTemplates\getManageBreakdancePostTypesSpaHtml");
        /** @psalm-suppress UndefinedFunction  */
        add_submenu_page('breakdance', 'Footers', 'Footers', 'manage_options', BREAKDANCE_FOOTER_POST_TYPE, "Breakdance\Themeless\ManageTemplates\getManageBreakdancePostTypesSpaHtml");
        /** @psalm-suppress UndefinedFunction  */
        add_submenu_page('breakdance', 'Global Blocks', 'Global Blocks', 'manage_options', BREAKDANCE_BLOCK_POST_TYPE, "Breakdance\Themeless\ManageTemplates\getManageBreakdancePostTypesSpaHtml");
        add_submenu_page('breakdance', 'Popups', 'Popups', 'manage_options', BREAKDANCE_POPUP_POST_TYPE, "Breakdance\Themeless\ManageTemplates\getManageBreakdancePostTypesSpaHtml");
        add_submenu_page('breakdance', 'Form Submissions', 'Form Submissions', 'manage_options', 'edit.php?post_type=breakdance_form_res');
        add_submenu_page('breakdance', 'Design Library', 'Design Library', 'manage_options', 'breakdance_design_library', "Breakdance\DesignLibrary\getDesignLibraryAppLoader");

        do_action('breakdance_admin_menu');
    }
}

function home()
{
    ?>
    <style>
        .breakdance-home, .breakdance-home p {
            font-size: 1.3em;
        }
    </style>
    <div class='breakdance-home'>
        <div style='display: flex; align-items: center; gap: 8px;'>
            <h1>
                Breakdance
            </h1>
                <small style='background-color: #ddd; border-radius: 4px; padding: 4px; font-weight: 600;'>
                    <?php
                    echo getSubscriptionMode();
                    ?>
                </small>
        </div>


        <ul>
            <li><a href='https://breakdance.com/facebook/' target="_blank">User Community</a></li>
            <li><a href='https://breakdance.com/tutorials/' target="_blank">Video Tutorials</a></li>
            <li><a href='https://breakdance.com/support/' target="_blank">Support</a></li>
            <li><a href='https://breakdance.com/documentation/' target="_blank">Documentation</a></li>
        </ul>

        <p>Copyright &copy; Soflyy. All Rights Reserved.</p>
    </div>


    <?php
}
