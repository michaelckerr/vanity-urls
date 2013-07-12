<?php
/*
Plugin Name: Vanity URLs
Plugin URI: uw.edu/giving
Description: Create shortened, vanity urls that point to other pages in your site
Author: Dane Odekirk
*/

class vanity_urls_page 
{

  const title = 'Vanity URLs';

  function vanity_urls_page() 
  {
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('template_redirect', array($this, 'redirect'));
    wp_enqueue_script('backbone');
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-smoothness', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.1/themes/smoothness/jquery-ui.css');
	}

  function redirect() 
  {
    $vanity_urls = get_option('_vanity_urls');
    $pagename = get_query_var('pagename');
    
    if ( is_array($vanity_urls) && array_key_exists($pagename, $vanity_urls ) ) 
    {
      $forward_to = $vanity_urls[$pagename]['to'];
      $url = filter_var($forward_to, FILTER_VALIDATE_URL) === false ?  home_url($forward_to) : $forward_to;
      wp_redirect( $url );
      exit;
    }
    return;
  }

  function save() 
  {
      $redirects = array_filter(array_map('array_filter', $_POST['vanity'] )); // magic to remove blank values
      foreach ($redirects as $redirect) {
        $redirect = array_filter(array_map('trim', $redirect)); // remove empty string values
        if ( isset($redirect['from']) && isset($redirect['to']) ) {
          $vanity_urls[strtolower($redirect['from'])]['from'] = trim($redirect['from']);
          $vanity_urls[strtolower($redirect['from'])]['to'] = trim($redirect['to']);
          $vanity_urls[strtolower($redirect['from'])]['created'] = $redirect['created'];
          $vanity_urls[strtolower($redirect['from'])]['expires'] = $redirect['expires'];
        }
      }
      update_option('_vanity_urls', $vanity_urls ); 
  }

  function admin_menu() 
  {
		add_options_page(self::title, self::title, 'manage_options', 'vanity_urls', array($this, 'settings_page'));
	}

  function settings_page() 
  {
    if ( isset($_POST['vanity']) ) self::save();

    ?>
    <div class="wrap">
      <div id="icon-options-general" class="icon32"><br></div>
      <h2><?php echo self::title; ?></h2>
      <p>
       Add or remove vanity urls for <?php bloginfo(); ?> site. The first column is the vanity url while the second column is the partial or full url to redirect to. All the urls are case insensitive. The expiration date will default to 30 days. Expired redirects will be labeled "Expired" in red but will still work and must be removed manually.
      </p>
      <p>
        <b>NOTE</b>: <br/><b>Permalinks must NOT be set to Default for redirects to work</b>.<br/> An example redirect would be putting 'hello' in the first input, then 'world' in the second input. Then when you go to <a target="_blank" href="<?php bloginfo('url') ?>/hello"><?php bloginfo('url') ?>/hello</a> you'll be redirected to <a target="_blank" href="<?php bloginfo('url') ?>/world"><?php bloginfo('url') ?>/world</a>.
      </p>
      
      <h3>List of redirects</h3>

    <form action="" method="post">
      <div id="vanity-list">
      </div>

      <p>
        <input id="add-vanity-url" type="button" class="button tagadd" value="Add New">
      </p>

      <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
    </form>

    </div>

<style>
.ui-datepicker-trigger { width:25px; position:absolute; }
</style>
<script type="text/template" id="redirect-template">
    <p class="redirect" style="background-color:read" data-count=<%= count %> >
      <input style="width:10%" name="vanity[<%= count %>][from]" type="text" value="<%= from  %>" class="regular-text url"/>
      &rarr;
      <input style="width:65%" name="vanity[<%= count %>][to]" type="text" value="<%= to %>" class="regular-text url"/>
      <input name="vanity[<%= count %>][posted]" type="hidden" value="<%= created %>"/>
      <input class="expires" name="vanity[<%= count %>][expires]" type="hidden" value="<%= expires %>"/>

      <input class="button delete remove-vanity-url" type="button" style="margin-left:30px;" value="Remove">
    <% if ( expired ) { %>
      <small style="color:red;"> Expired </small>
    <% } %>
    </p>
</script>


    <script type="text/javascript">

    jQuery(window).load(function() {
      jQuery( ".expires" ).datepicker();
    })

      jQuery(document).ready(function($) {

        var DEFAULT_DATE = 30;

        $.datepicker.setDefaults({
          showOn: "button",
          buttonImage: "/cms/wp-content/themes/maps/images/date.gif",
          buttonImageOnly: true,
          dateFormat: '@',
          defaultDate: DEFAULT_DATE
        })


        $('#add-vanity-url').click(function() {
          var $list = $('p.redirect')
            , $last = $list.last()

          $('#vanity-list').append(
            _.template( $('#redirect-template').html(), { count : $last.data('count') + 1 || 0, from:null, to:null, created:null, expires:null, expired:null })
          ).find('.expires').datepicker().datepicker('setDate', DEFAULT_DATE)

        })

        $('#vanity-list').on('click', 'input.remove-vanity-url', function() {

          var $this = $(this)
              $list = $('p.redirect') 

            if ( $list.length === 1)  {
              $this.attr('disabled', true)
              return;
            }

          $this.parent('p').remove() 

        })

        $('#vanity-list').on('blur', 'input.url', function() {
          var $this = $(this)
          $this.val($.trim($this.val()));
        })


        var redirects = <?php echo json_encode(get_option('_vanity_urls')) ?>;


        $.each(redirects, function(index,el) {
          //var expired  = $.datepicker.parseDate('@', el.expires).getTime() - new Date() < 0;
          var expired  = Number(el.expires) - new Date() < 0;
          $('#vanity-list').append(
            _.template( $('#redirect-template').html(), { count : index, from:el.from, to:el.to, created:el.created || 5, expires:el.expires, expired:expired })
          )

        })

      })  

    </script>

    <?php
    
	}

}

new vanity_urls_page;
