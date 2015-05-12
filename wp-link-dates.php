<?php

namespace WP_Link_Dates;

/*
Plugin Name: WP Link Dates
Version: 0.1-alpha
Description: Admin interface for 'link_updated' link column.
Author: Zlatko Zlatev
Author URI: http://wptailor.com/
Plugin URI: http://wptailor.com/
*/

/**
 * [link_date_meta_box description]a
 * @param  [type] $link [description]
 * @return [type]       [description]
 */
function link_date_meta_box( $link ) {

  global $comment;
  $comment = new \stdClass;
  $comment->comment_date = $link->link_updated;

  /**
   * Print PostI18n.
   */
  wp_scripts()->print_extra_script( 'post' );

  $datef = __( 'M j, Y @ H:i' );
  if ( 0 != $link->link_id ) {
      $stamp = __('Published on: <b>%1$s</b>');
    $date = date_i18n( $datef, strtotime( $link->link_updated ) );
  } else { // draft (no saves, and thus no date specified)
    $stamp = __('Publish <b>immediately</b>');
    $date = date_i18n( $datef, strtotime( current_time('mysql') ) );
  }

  ?>
  <div class="misc-pub-section curtime misc-pub-curtime">
    <span id="timestamp">
    <?php printf( $stamp, $date ); ?></span>
    <a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><span aria-hidden="true"><?php _e( 'Edit' ); ?></span> <span class="screen-reader-text"><?php _e( 'Edit date and time' ); ?></span></a>
    <div id="timestampdiv" class="hide-if-js"><?php touch_time(($action == 'edit'), 0); ?></div>
  </div><?php // /misc-pub-section ?>
  <script>
  (function($) {
    var $timestampdiv, updateText, stamp = $('#timestamp').html();

    updateText = function() {

      if ( ! $timestampdiv.length )
        return true;

      var attemptedDate, originalDate, currentDate, publishOn, postStatus = $('#post_status'),
        optPublish = $('option[value="publish"]', postStatus), aa = $('#aa').val(),
        mm = $('#mm').val(), jj = $('#jj').val(), hh = $('#hh').val(), mn = $('#mn').val();

      attemptedDate = new Date( aa, mm - 1, jj, hh, mn );
      originalDate = new Date( $('#hidden_aa').val(), $('#hidden_mm').val() -1, $('#hidden_jj').val(), $('#hidden_hh').val(), $('#hidden_mn').val() );
      currentDate = new Date( $('#cur_aa').val(), $('#cur_mm').val() -1, $('#cur_jj').val(), $('#cur_hh').val(), $('#cur_mn').val() );

      if ( attemptedDate.getFullYear() != aa || (1 + attemptedDate.getMonth()) != mm || attemptedDate.getDate() != jj || attemptedDate.getMinutes() != mn ) {
        $timestampdiv.find('.timestamp-wrap').addClass('form-invalid');
        return false;
      } else {
        $timestampdiv.find('.timestamp-wrap').removeClass('form-invalid');
      }

      if ( attemptedDate > currentDate ) {
        publishOn = postL10n.publishOnFuture;
        // $('#publish').val( postL10n.schedule );
      } else if ( attemptedDate <= currentDate ) {
        publishOn = postL10n.publishOn;
        // $('#publish').val( postL10n.publish );
      } else {
        publishOn = postL10n.publishOnPast;
        // $('#publish').val( postL10n.update );
      }
      if ( originalDate.toUTCString() == attemptedDate.toUTCString() ) { //hack
        $('#timestamp').html(stamp);
      } else {
        $('#timestamp').html(
          publishOn + ' <b>' +
          postL10n.dateFormat.replace( '%1$s', $('option[value="' + $('#mm').val() + '"]', '#mm').text() )
            .replace( '%2$s', jj )
            .replace( '%3$s', aa )
            .replace( '%4$s', hh )
            .replace( '%5$s', mn ) +
            '</b> '
        );
      }

      return true;
    };

    $(function() {
      $timestampdiv = $('#timestampdiv');

      $timestampdiv.siblings('a.edit-timestamp').click( function( event ) {
        if ( $timestampdiv.is( ':hidden' ) ) {
          $timestampdiv.slideDown('fast');
          $('#mm').focus();
          $(this).hide();
        }
        event.preventDefault();
      });

      $timestampdiv.find('.cancel-timestamp').click( function( event ) {
        $timestampdiv.slideUp('fast').siblings('a.edit-timestamp').show().focus();
        $('#mm').val($('#hidden_mm').val());
        $('#jj').val($('#hidden_jj').val());
        $('#aa').val($('#hidden_aa').val());
        $('#hh').val($('#hidden_hh').val());
        $('#mn').val($('#hidden_mn').val());
        updateText();
        event.preventDefault();
      });

      $timestampdiv.find('.save-timestamp').click( function( event ) { // crazyhorse - multiple ok cancels
        if ( updateText() ) {
          $timestampdiv.slideUp('fast');
          $timestampdiv.siblings('a.edit-timestamp').show();
        }
        event.preventDefault();
      });
    });
  })(jQuery)

  </script>
  <?php
  // return $link;
}

/**
 * [add_meta_boxes description]
 * @param [type] $page_type [description]
 * @param [type] $link      [description]
 */
function add_meta_boxes_link( $link ) {

  add_meta_box('linkdatediv', __( 'Link date' ), __NAMESPACE__ . '\\link_date_meta_box', null, 'side', 'high' );
}

add_action( 'add_meta_boxes_link', __NAMESPACE__ . '\\add_meta_boxes_link', 10, 2 );

function _get_post_link_updated() {

  $post_data = &$_POST;

  foreach ( array('aa', 'mm', 'jj', 'hh', 'mn') as $timeunit ) {
    if ( !empty( $post_data['hidden_' . $timeunit] ) && $post_data['hidden_' . $timeunit] != $post_data[$timeunit] ) {
      $post_data['edit_date'] = '1';
      break;
    }
  }
  $link_updated = false;
  if ( !empty( $post_data['edit_date'] ) ) {
    $aa = $post_data['aa'];
    $mm = $post_data['mm'];
    $jj = $post_data['jj'];
    $hh = $post_data['hh'];
    $mn = $post_data['mn'];
    $ss = $post_data['ss'];
    $aa = ($aa <= 0 ) ? date('Y') : $aa;
    $mm = ($mm <= 0 ) ? date('n') : $mm;
    $jj = ($jj > 31 ) ? 31 : $jj;
    $jj = ($jj <= 0 ) ? date('j') : $jj;
    $hh = ($hh > 23 ) ? $hh -24 : $hh;
    $mn = ($mn > 59 ) ? $mn -60 : $mn;
    $ss = ($ss > 59 ) ? $ss -60 : $ss;
    $link_updated = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, $jj, $hh, $mn, $ss );
    $valid_date = wp_checkdate( $mm, $jj, $aa, $link_updated );
    if ( !$valid_date ) {
      return new WP_Error( 'invalid_date', __( 'Whoops, the provided date is invalid.' ) );
    }
  }
  return $link_updated;
}

/**
 * [save_link_updated description]
 * @return [type] [description]
 */
function save_link_updated( $link_id ) {

  if ( false !== ( $link_updated = _get_post_link_updated() ) || ( current_filter() == 'add_link' && $link_updated = current_time('mysql') ) ) {
    global $wpdb;

    $wpdb->update( $wpdb->links, array( 'link_updated' => $link_updated ), array( 'link_id' => $link_id ) );
  }
}

add_action( 'edit_link', __NAMESPACE__ . '\\save_link_updated' );
add_action( 'add_link', __NAMESPACE__ . '\\save_link_updated' );
