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
            "SELECT SQL_CALC_FOUND_ROWS email.* , \n            sum( if(stats.event = 0, 1 , 0) ) as lu , \n            sum( if(stats.event = 1, 1, 0) ) as clicks  , \n            case \n                when in_queue = 1 then 'Queued' \n                when fail is null then 'Sent' \n                else 'Fail' \n            end as status\n            FROM `{$mail_table}` as email \n            left join `{$event_table}` as stats on email.id = stats.email_id \n            WHERE `email`.`date_time` between %s and %s \n            group by email.id ORDER BY {$order_clause}\n            limit %d offset %d",
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
            'to'        => __( 'To', 'mail-control' ),
            'subject'   => __( 'Subject', 'mail-control' ),
            'content'   => __( 'Excerpt', 'mail-control' ),
            'status'    => __( 'Status', 'mail-control' ),
            'open'      => __( 'Reads', 'mail-control' ),
            'click'     => __( 'Clicks', 'mail-control' ),
        ] );
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

}