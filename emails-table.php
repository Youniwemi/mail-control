<?php

namespace Mail_Control;

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
class Emails_Table extends \WP_List_Table
{
    public  $from ;
    public  $to ;
    public function __construct()
    {
        //Set parent defaults
        parent::__construct( array(
            'singular' => 'email',
            'plural'   => 'emails',
            'ajax'     => true,
        ) );
    }
    
    protected function get_table_classes()
    {
        $mode = get_user_setting( 'posts_list_mode', 'list' );
        $mode_class = esc_attr( 'table-view-' . $mode );
        return array(
            'widefat',
            'striped',
            $mode_class,
            $this->_args['plural']
        );
    }
    
    public function prepare_items()
    {
        $per_page = $this->get_items_per_page( 'per_page', 20 );
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $current_page = $this->get_pagenum();
        $this->from = ( isset( $_GET['from'] ) ? new \DateTime( $_GET['from'] ) : new \DateTime( '-1 month' ) );
        $this->to = ( isset( $_GET['to'] ) ? new \DateTime( $_GET['to'] ) : new \DateTime( 'now' ) );
        global  $wpdb ;
        $order = 'date_time';
        $direction = 'DESC';
        
        if ( isset( $_REQUEST['orderby'] ) ) {
            $sortable_columns = array_map( function ( $column ) {
                return $column[0];
            }, $sortable );
            // Make sure $_REQUEST['orderby'] is a valid sortable column
            
            if ( in_array( $_REQUEST['orderby'], $sortable_columns ) ) {
                $order = $_REQUEST['orderby'];
                $direction = ( isset( $_REQUEST['order'] ) && $_REQUEST['order'] == 'desc' ? 'DESC' : 'ASC' );
            }
        
        }
        
        $mail_table = $wpdb->prefix . MC_EMAIL_TABLE;
        $event_table = $wpdb->prefix . MC_EVENT_TABLE;
        $order_clause = sanitize_sql_orderby( "`{$order}` {$direction}" );
        $sql = $wpdb->prepare(
            "SELECT SQL_CALC_FOUND_ROWS email.* , \n            sum( if(stats.event = 0, 1 , 0) ) as lu , \n            sum( if(stats.event = 1, 1, 0) ) as clicks  , \n            case \n                when in_queue = 1 then 0 \n                when fail is null then 1\n                else -1 \n            end as status\n            FROM `{$mail_table}` as email \n            left join `{$event_table}` as stats on email.id = stats.email_id \n            WHERE `email`.`date_time` between %s and %s \n            group by email.id ORDER BY {$order_clause}\n            limit %d offset %d",
            $this->from->format( 'Y-m-d 00:00:00' ),
            $this->to->format( 'Y-m-d 23:59:59' ),
            $per_page,
            $per_page * ($current_page - 1)
        );
        $results = $wpdb->get_results( $sql );
        $this->items = array_map( [ $this, 'prepare_data' ], $results );
        $total_items = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ) );
    }
    
    public function extra_tablenav( $which )
    {
        ?>
        <div class="alignleft actions">
            <label><?php 
        echo  _e( 'From', 'mail-control' ) ;
        ?><input type="date" name="from" value="<?php 
        echo  $this->from->format( 'Y-m-d' ) ;
        ?>"/></label>
            <label><?php 
        echo  _e( 'To', 'mail-control' ) ;
        ?><input type="date" name="to" value="<?php 
        echo  $this->to->format( 'Y-m-d' ) ;
        ?>" /></label>
            <?php 
        submit_button(
            __( 'Filter' ),
            '',
            'filter_action',
            false
        );
        ?>
        </div>
        <?php 
    }
    
    public function prepare_data( $row )
    {
        return apply_filters( 'emails_table_columns_data', [
            'id'        => $row->id,
            'date_time' => $row->date_time,
            'to'        => $row->to,
            'subject'   => $row->subject,
            'content'   => wp_trim_words( $row->message_plain, 10 ),
            'status'    => $row->status,
            'open'      => $row->lu,
            'click'     => $row->clicks,
        ], $row );
    }
    
    public function get_columns()
    {
        return apply_filters( 'emails_table_columns_headers', [
            'date_time' => __( 'Date', 'mail-control' ),
            'to'        => __( 'Recepient', 'mail-control' ),
            'subject'   => __( 'Subject', 'mail-control' ),
            'content'   => __( 'Excerpt', 'mail-control' ),
            'status'    => __( 'Status', 'mail-control' ),
            'open'      => __( 'Reads', 'mail-control' ),
            'click'     => __( 'Clicks', 'mail-control' ),
            'detail'    => __( 'Detail', 'mail-control' ),
        ] );
    }
    
    public function column_detail( $item )
    {
        $url = add_query_arg( [
            'id'     => $item['id'],
            'action' => 'detail_email',
            'width'  => 800,
            'height' => 700,
        ], admin_url( "admin-ajax.php" ) );
        $detail = '<a href="' . esc_url( $url ) . '" title="' . esc_attr__( 'Email Details', 'mail-control' ) . '" class="thickbox button button-secondary" >' . __( 'Show details', 'mail-control' ) . '</a>';
        $url = add_query_arg( [
            'id'     => $item['id'],
            'action' => 'resend_email',
            'width'  => 300,
            'height' => 200,
        ], admin_url( "admin-ajax.php" ) );
        $actions = [
            'resend' => '<a class="thickbox" title="' . esc_attr__( 'Resend Email', 'mail-control' ) . '"  href="' . esc_url( $url ) . '">' . esc_html__( 'Resend', 'mail-control' ) . '</a>',
        ];
        return sprintf( '%1$s %2$s', $detail, $this->row_actions( $actions ) );
    }
    
    public function column_status( $item )
    {
        switch ( $item['status'] ) {
            case 0:
                return '<mark class="queued">' . esc_html__( 'Queued', 'mail-control' ) . '</mark>';
            case -1:
                return '<mark class="failed">' . esc_html__( 'Failed', 'mail-control' ) . '</mark>';
            case 1:
                return '<mark class="sent">' . esc_html__( 'Sent', 'mail-control' ) . '</mark>';
        }
    }
    
    public function get_sortable_columns()
    {
        return apply_filters( 'emails_table_columns_sortable_headers', [
            'date_time' => array( 'date_time', false ),
            'to'        => array( 'to', false ),
            'subject'   => array( 'subject', false ),
            'content'   => array( 'message_plain', false ),
            'status'    => array( 'status', false ),
            'open'      => array( 'lu', false ),
            'click'     => array( 'clicks', false ),
        ] );
    }
    
    public function column_default( $item, $column_name )
    {
        
        if ( isset( $item[$column_name] ) ) {
            return $item[$column_name];
        } else {
            return '';
        }
    
    }
    
    public static function normalize_headers( $headers )
    {
        $arrayHeaders = [];
        foreach ( $headers as $line ) {
            
            if ( is_string( $line ) ) {
                
                if ( strpos( $line, ':' ) !== false ) {
                    list( $header, $value ) = array_map( 'trim', explode( ':', $line ) );
                    $arrayHeaders[$header] = $value;
                }
            
            } else {
                [ $header, $value ] = $line;
                $arrayHeaders[$header] = $value;
            }
        
        }
        if ( count( $arrayHeaders ) ) {
            $headers = $arrayHeaders;
        }
        return $headers;
    }

}