<?php

namespace Editor;

// this editor menu is the same for both directory and picture, picture items are done on the highlighted object
function editor_menu( $item , $user ) {
    ?>
    <div class="image_editor" type="<?php print $item->get('directory')?'directory':'file'; ?>">
     <ul>
      <li action="" class="directories addTags"></li> 
      <li action="addTags" class="files addTags"><div contenteditable="true">add tag...</div></li> 
      <li action="deleteTags" class="files showTags" preset="0"></li>
      <li action="personalRating" class="files personalRating" preset="0">Rating</li>
      <li action="rotateRight" class="files directories rotateRight">Rotate CW</li>
      <li action="rotateLeft" class="files directories rotateLeft">Rotate CCW</li>
      <li action="highlight" class="files directories highlight">Highlight</li>
      <li class="spacer"> </li>
      <li action="delete" class="files directories delete">Delete</li>
     </ul>
    </div>
    <?php
}
\hooks::add('thumbnail_addon_top', __NAMESPACE__.'\editor_menu');

function editor_action_albums($json, $item, $album) {
    $parent = $album->getParent();
    $item->set('path', $album->path() );
    
    switch($json->Item) {
        case 'displayname':
                $item->rename($json->Value, $item->get('description'))->save();
                //$response->Href='/albums/'.urlencode($item->get('path')).'/'.$item->get('name');
                ///$response->Id = $item->get('itemid');
                return array("Id" => $item->get('itemid'), "Href" => '/albums/'.urlencode($item->get('path')).'/'.$item->get('name') );
            break;
        case 'description':
                $item->rename($item->get('displayname'), $json->Value)->save();
                //$response->Href='/albums/'.urlencode($item->get('path')).'/'.$item->get('name');
                //$response->Id = $item->get('itemid');
                return array("Id" => $item->get('itemid'), "Href" => '/albums/'.urlencode($item->get('path')).'/'.$item->get('name') );
            break;
        case (substr($json->Item,0,4) == 'drop'): // we're dropping an item, get current folder status
                // $item = target
                // $json->Value = source array

                $albumItems = $album->findChildren()
                               //->sortAlbum(  $album->getParent()->get('sortby') , $album->getParent()->get('sortorder') ) // 268ms
                               ->sortAlbum( 
                                    array( 
                                       'directory' => 'desc',
                                        $album->getParent()->get('sortby') => $album->getParent()->get('sortorder')
                                    ) 
                                )
                               ->getItems();

                // if we are swapping items, we can do so if its 1
                if ($json->Item == 'dropReplace') {
                    $index_destination = $album->findObjectIndex('itemid', $json->Id);
                    $index_source = $album->findObjectIndex('itemid', $json->Value[0]);
                    $temp = $albumItems[ $index_destination ];
                    $albumItems[ $index_destination ] = $albumItems[ $index_source ];
                    $albumItems[ $index_source ] = $temp;
                    if (sizeof($json->Value) > 1) {
                        // add all remaining items after the destination
                        $json->Item = 'dropRight';
                        $json->Id = array_shift($json->Value);
                    }
                    $newlist = $albumItems;
                }

                // if we're moving before or after - splitup the array
                if (($json->Item == 'dropLeft') || ($json->Item == 'dropRight')) {

                    // get our items to change, and remove them from the original array
                    foreach($json->Value as $changeItem) {
                        $newitems[] = $albumItems[ $album->findObjectIndex('itemid', $changeItem) ];
                        unset($albumItems[ $album->findObjectIndex('itemid', $changeItem) ]);
                    }

                    // get the offset now that we removed items from the remaining array
                    $count=0;
                    foreach ($albumItems as $col) {
                        if ($col->get('itemid') == $item->get('itemid')) { $offset = $count; break; }
                        $count++;
                    }
                    // split the array in to pieces
                    $before = array_splice($albumItems, 0, $offset);
                    $destination = array_shift($albumItems);
                    $after = $albumItems;

                } 
                if ($json->Item == 'dropLeft') {
                    // drop new items left of destination
                    foreach($before as $a) { $newlist[] = $a; }
                    foreach($newitems as $a) { $newlist[] = $a; }
                    $newlist[] = $destination ;
                    foreach($after as $a) { $newlist[] = $a; }

                } elseif ($json->Item == 'dropRight') {
                    // drop new items right of destination
                    foreach($before as $a) { $newlist[] = $a; }
                    $newlist[] = $destination ;
                    foreach($newitems as $a) { $newlist[] = $a; }
                    foreach($after as $a) { $newlist[] = $a; }

                }
                $counter = 0;
                foreach ($newlist as $newItem) {
                    $newItem->set('orderid', ++$counter)->save();
                }
                $parent->set('sortby', 'orderid')->save();
                $parent->set('sortorder', 'asc')->save();

            break;
        case 'highlight':
            $parent = $album->getParent();

            if ($item->get('directory') == 1) {
                $parent->set('highlight', $item->get('highlight'))->save();
            } else {
                $parent->set('highlight', $item->get('itemid'))->save();
            }
            /*
                $item->rename($json->Value, $item->get('description'))->save();
                $response->Href='/albums/'.urlencode($item->get('path')).'/'.$item->get('name');
                $response->Id = $item->get('itemid');
            */
            return array("Message" => $item->get('displayname')." is now set as the albums Highlight for ".$parent->get('displayname'), "Code" => "1");
            break;
        case 'rotateLeft':
            if ($item->get('highlight') === 0) { // empty directories
                throw new \Exception("There is no image attached to item", 404);
            } else if ($item->get('highlight') > 0) { // highlights of directories
                $highlight = $item->get('highlight');
                $item = new \ItemObject();
                $item->getId($highlight);

                $parent = $album->initAlbumById( $item->get('parentid') )->getParent();
                $path = $album->path();
                $item->set('path', $path);

            }
            $cache = new \CacheObject( $item );
            $cache->loadItem( $item->get('itemid') );
            $item->set('cache', $cache)
                 ->rotateLeft()->save()
                 ->get('cache')->clear();

            // FIXME: we should be able to handle this if we're not a thumbnail
            return array("Message" => false, "Resize" => true, "SwapXY" => true, "Cache" => $item->get('cache')->getUrl(THUMB_SIZE));
            break;
        case 'rotateRight':
            if ($item->get('highlight') === 0) { // empty directories
                throw new \Exception("There is no image attached to item", 404);
            } else if ($item->get('highlight') > 0) { // highlights of directories
                $highlight = $item->get('highlight');
                $item = new \ItemObject();
                $item->getId($highlight);

                $parent = $album->initAlbumById( $item->get('parentid') )->getParent();
                $path = $album->path();
                $item->set('path', $path);
            }
            $cache = new \CacheObject( $item );
            $cache->loadItem( $item->get('itemid') );
            $item->set('cache', $cache)
                 ->rotateRight()->save()
                 ->get('cache')->clear();

            return array("Message" => false, "Resize" => true, "SwapXY" => true,  "Cache" => $item->get('cache')->getUrl(THUMB_SIZE));
            break;
        case 'delete':
            $item->delete();
            return array("Message" => $item->get('displayname')." has been deleted", "Resize" => true, "Remove" => true);
            break;
        case 'getRating':
            return array("Rating" => $item->get('rating')?$item->get('rating'):0 );
            break;
        case 'setRating':
            //list($undef, $rating) = explode('_', $json->Item);
            if ($item->get('rating') == $json->Value) { $json->Value = 0; } // when we select the same rating, we remove it
            $item->set('rating', (int)$json->Value)->save()->saveRating();

            //$meta = new constant(METADATA_HANDLER) ();

            //\Images\imagesProcessing::updateRating($item);
            return array("Rating" => $item->get('rating'));
            break;
        case 'getTags':
            $item->getKeywords();
            return array("Tags" => $item->get('keywords') );
            break;
        /*case (substr($json->Item, 0, 7) == 'addTag_'):*/
        case 'addTag':
            $item->getKeywords();
            $keywords = $item->get('keywords');

            //list($undef, $tag) = explode('_', $json->Item);
            $keywords[] = $json->Value;
            $item->set('keywords', $keywords)->save()->saveKeywords();
            //\Images\imagesProcessing::updateKeywords($item);
            return array("Tags" => $item->get('keywords') );
            break;
        /* case (substr($json->Item, 0, 10) == 'deleteTag_'):*/
        case 'deleteTag':
            $item->getKeywords();
            $keywords = $item->get('keywords');

            //list($undef, $tag) = explode('_', $json->Item);
            if (($key = array_search($json->Value, $keywords)) !== false) {
               unset($keywords[$key]);
            }
            $item->set('keywords', $keywords)->save()->saveKeywords();
            //\Images\imagesProcessing::updateKeywords($item);
            return array("Tags" => $item->get('keywords') );
            break;

    }

}
\hooks::add('put_article_albums_action', __NAMESPACE__.'\editor_action_albums');

?>