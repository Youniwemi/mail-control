<?php

namespace Mail_Control;

require __DIR__ . '/emails-table.php';
function admin_menu()
{
    add_menu_page(
        'Mail Control',
        __( 'Mail Control', 'mail-control' ),
        'edit_posts',
        'mail-control',
        'Mail_Control\\show_email_table',
        "dashicons-email"
    );
}

function show_email_table()
{
    $emails = new Emails_Table();
    $emails->prepare_items();
    ?>
    <div class="wrap">
    <h1><?php 
    echo  esc_html( get_admin_page_title() ) ;
    ?></h1>

    <form id="emails-table" method="get">        
        <input type="hidden" name="page" value="<?php 
    echo  esc_attr( $_REQUEST['page'] ) ;
    ?>" />
        <?php 
    $emails->display();
    ?>
    </form>
    </div>
    <?php 
}

add_action( 'admin_menu', 'Mail_Control\\admin_menu', 0 );