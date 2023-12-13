<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the reports view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Sometimes this view is filtered to a report's data.
if(!empty($_GET['report'])){

    // Load the data of a selected report.
    $filtered_to_report = array(
        array(
            'name' => 'meta_name',
            'value' => $_GET['report']
        )
    );
    $report = (array)DataAccess::get_db_rows(
        'meta', $filtered_to_report
    )['content'][0];

    // We need to unserialize the meta from the report.
    $report_meta = unserialize($report['meta_value']);

    // No archived notices are shown in reports.
    array_push($report_meta, array(
        'name' => 'archived',
        'value' => 0
    ));

    // Let's prepare the tags to be queried by the db.
    $tags = array();
    if(!empty($report_meta)){
        foreach ($report_meta as $key => $meta ){
            if($meta['value'] == 'on'){
                $tags[] = array(
                    'value' => $meta['name'],
                    'column' => 'tags'
                );
                unset($report_meta[$key]);
            }
        }
    }
    if(!empty($tags)){
        $report_meta[] = array(
            'name' => '',
            'type' => 'find_in_set',
            'value' => $tags
        );
    }

// We also have special presets.
}elseif(!empty($_GET['preset'])){

    // The active preset contains all notices.
    if($_GET['preset'] == 'all'){
        $report = array(
            'meta_name' => 'report_all',
            'meta_value' => array(
                array(
                    'name' => 'title',
                    'value' => 'All Notices'
                ), array(
                    'name' => 'archived',
                    'value' => 0
                )
            )
        );
        $report_meta = $report['meta_value'];    

    // The ignored preset contains all 'ignored' notices.
    }elseif($_GET['preset'] == 'ignored'){
        $report = array(
            'meta_name' => 'report_ignored',
            'meta_value' => array(
                array(
                    'name' => 'title',
                    'value' => 'Ignored Notices'
                ),
                array(
                    'name' => 'status',
                    'value' => 'ignored'
                ), array(
                    'name' => 'archived',
                    'value' => 0
                )
            )
        );
        $report_meta = $report['meta_value'];

    // The equalified preset contains all 'equalified' 
    // notices.
    }elseif($_GET['preset'] == 'equalified'){
        $report = array(
            'meta_name' => 'report_equalified',
            'meta_value' => array(
                array(
                    'name' => 'title',
                    'value' => 'Equalified Notices'
                ),
                array(
                    'name' => 'status',
                    'value' => 'equalified'
                ), array(
                    'name' => 'archived',
                    'value' => 0
                )
            )
        );
        $report_meta = $report['meta_value'];
        
    }

    // We have no tags.
    $tags = array();

}else{

    // When there's no report data, we get active notices.
    $report = array(
        'meta_name' => 'report_active',
        'meta_value' => array(
            array(
                'name' => 'title',
                'value' => 'Active Notices'
            ),
            array(
                'name' => 'status',
                'value' => 'active'
            ), array(
                'name' => 'archived',
                'value' => 0
            )
        )
    );
    $report_meta = $report['meta_value'];

    // We have no tags.
    $tags = array();

}


// Let's extract the "title" meta, so we can use it 
// later and so we can use any report's meta_values to
// filter the notices.
foreach($report_meta as $k => $val) {
    if($val['name'] == 'title') {
        $the_title = $val['value'];
        unset($report_meta[$k]);
        
        // We'll also add text to non-presets.
        if(empty($_GET['preset']))
            $the_title.= ' Report';

    }
}

// PoC of adding basic JSON option
if(!empty($_GET['format'])){
    // JSON version of View
    $filters = $report_meta;
    $notices = DataAccess::get_db_rows( 'notices',
        $filters, get_current_page_number()
    );
    if( count($notices['content']) > 0 ): 
        foreach($notices['content'] as $notice):   
            echo json_encode( $notice ); 
        endforeach;
    endif;
} else {
?>

<section>
    <div class="mb-3 pb-3 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h1><?php echo $the_title;?></h1>
        </div>

        <?php
        // Hide filters on pages that are not reports.
        if(!empty($_GET['report'])):
        ?>

        <div> 

            <?php
            // We'll count the active filters.
            $filters_count = 0;
            if(!empty($report_meta)){
                foreach($report_meta as $meta){
                    
                    // We count tags individually.
                    if(!empty($meta['type']) && $meta['type'] == 'find_in_set'){
                        $filters_count = count($meta['value'])+$filters_count;
                    
                    // We also don't count the archived filter, since that's set by default.
                    }elseif($meta['name'] == 'archived'){

                    // We'll count the remaining filters.
                    }else{
                        $filters_count++;
                    }

                }
            }
            if($filters_count > 0)
                echo '<span class="badge text-bg-secondary">'.$filters_count.' Active Filters</span>';
            ?>
                       
            <a href="index.php?view=report_settings&meta_name=<?php echo $report['meta_name']; ?>" class="btn btn-primary">
                Filters & Settings
            </a>
        </div>

        <?php
        // End filter hider.
        endif;
        ?>

    </div>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">URL</th>
                <th scope="col">Message</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>

        <?php
        // We need to setup the different filters from the
        // all report meta.
        $filters = $report_meta;
        $notices = DataAccess::get_db_rows( 'notices',
            $filters, get_current_page_number()
        );
        if( count($notices['content']) > 0 ): 
            foreach($notices['content'] as $notice):    
        ?>

        <tr>
            <td><?php echo $notice->time;?></td>
            <td><?php echo $notice->related_url;?></td>
            <td><?php echo covert_code_shortcode($notice->message);?></td>
            <td style="min-width: 200px;">

                <?php
                // Conditionally show "More Info".
                if(!empty($notice->more_info)){
                ?>

                <a href="?view=single_notice&id=<?php echo $notice->id;?>" class="btn btn-outline-primary btn-sm">
                    More Info
                </a>

                <?php
                }

                // Active notices can be ignored.
                if( $notice->status == 'active' && $notice->archived != 1 ){
                ?>

                <a href="actions/ignore_notice.php?id=<?php echo $notice->id; if(isset($_GET['report'])) echo '&report='.$_GET['report']; if(isset($_GET['preset'])) echo '&preset='.$_GET['preset'];?>" class="btn btn-outline-secondary btn-sm">
                    Ignore Notice
                </a>

                <?php
                // Ignored notices can be activated.
                }elseif( $notice->status == 'ignored' ){
                ?>

                <a href="actions/activate_notice.php?id=<?php echo $notice->id; if(isset($_GET['report'])) echo '&report='.$_GET['report']; if(isset($_GET['preset'])) echo '&preset='.$_GET['preset'];?>" class="btn btn-outline-success btn-sm">
                    Activate Notice
                </a>

                <?php
                }
                ?>

            </td>
        </tr>

        <?php 
        // Fallback.
        endforeach; else:
        ?>

        <tr>
            <td colspan="6">
                <?php ob_start(); ?>
                <p class="text-center my-2 lead">
                    No notices found.<br>
                </p>
                <p class="text-center my-2">
                    <img src="plumeria.png" alt="Three frangipani flowers. The flowers have five petals. Color emanates from the center of the flower before becoming colorless at the tip of each petal."  ><br>
                    <strong>Get out and smell the frangipani!</strong>
                </p>
                <?php
					// @TODO: run the new hooks system instead
					//echo $hook_system->apply_filters('no_notices_fallback', ob_get_clean());
					echo ob_get_clean();
				?>
            </td>
        </tr>

        <?php 
        // End Notices
        endif;
        ?>

    </table>

    <?php
    // The pagination
    the_pagination($notices['total_pages']);
    ?>

</section>

<?php } ?>