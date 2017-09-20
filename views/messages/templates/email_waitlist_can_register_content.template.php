<table class="head-wrap" bgcolor="#999999">
    <tbody>
    <tr>
        <td></td>
        <td class="header container">
            <div class="content">
                <table bgcolor="#999999">
                    <tbody>
                    <tr>
                        <td>[CO_LOGO]</td>
                        <td align="right">
                            <h6 class="collapse">[COMPANY]</h6>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </td>
        <td></td>
    </tr>
    </tbody>
</table>

<table class="body-wrap">
    <tbody>
    <tr>
        <td></td>
        <td class="container" bgcolor="#FFFFFF">
            <div class="content">
                <table>
                    <tbody>
                    <tr>
                        <td>
                            <p class="lead">
                                <?php
                                printf(
                                    esc_html__(
                                        'A spot has opened up for you at: %1$s%2$s%3$s.',
                                        'event_espresso'
                                    ),
                                    '<a href="[EVENT_URL]">',
                                    '[EVENT_NAME]',
                                    '</a>'
                                );
                                ?>
                            </p>
                            <h3>
                                <?php
                                    printf(
                                        esc_html__(
                                            '%1$sClick Here to Finalize Your Registration Now%2$s',
                                            'event_espresso'
                                        ),
                                        '<a href="[RECIPIENT_WAITLIST_REGISTRATION_URL]">',
                                        '</a>'
                                    );
                                ?>
                            </h3>
                            <p>
                                <strong><?php esc_html_e('Details about your selection:', 'event_espresso'); ?></strong>
                            </p>
                            <p>
                                [TICKET_NAME]
                            </p>
                            <p>
                                <?php
                                    esc_html_e(
                                        'Please remember you have a limited time to sign up! If you have any questions, or no longer wish to register, then please reply to get in touch.',
                                        'event_espresso'
                                    );
                                ?>
                            </p>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </td>
        <td></td>
    </tr>
    </tbody>
</table>

<table class="footer-wrap">
    <tbody>
    <tr>
        <td></td>
        <td class="container">
            <table class="social" width="100%">
                <tbody>
                <tr>
                    <td>
                        <table class="column" align="left">
                            <tbody>
                            <tr>
                                <td>
                                    <h5><?php esc_html_e('Connect with Us:', 'event_espresso'); ?></h5>
                                    <a class="soc-btn fb" href="[CO_FACEBOOK_URL]">
                                        <?php esc_html_e('Facebook', 'event_espresso'); ?>
                                    </a>
                                    <a class="soc-btn tw" href="[CO_TWITTER_URL]">
                                        <?php esc_html_e('Twitter', 'event_espresso'); ?>
                                    </a>
                                    <a class="soc-btn gp" href="[CO_GOOGLE_URL]">
                                        <?php esc_html_e('Google+', 'event_espresso'); ?>
                                    </a>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <table class="column" align="left">
                            <tbody>
                            <tr>
                                <td>
                                    <h5><?php esc_html_e('Contact Info:', 'event_espresso'); ?></h5>
                                    <?php esc_html_e('Phone:', 'event_espresso'); ?> <strong>[CO_PHONE]</strong>
                                    <?php esc_html_e('Email:', 'event_espresso'); ?>
                                    <strong><a href="mailto:[CO_EMAIL]" target="_blank">[CO_EMAIL]</a></strong>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        &nbsp;
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
        <td></td>
    </tr>
    </tbody>
</table>
