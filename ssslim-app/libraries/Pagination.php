<?php
/**
 * Created by IntelliJ IDEA.
 * User: vds
 * Date: 12/10/2018
 * Time: 12:05
 */

namespace Ssslim\Libraries;

use CI_Loader;

class Pagination

{
    private $itemsPerPage = 0;
    private $paginationLinks = 0;

    /**
     * @var CI_Loader
     */
    private $load;

    /**
     * Pagination constructor.
     * @param CI_Loader $load
     */
    public function __construct(CI_Loader $load)
    {
        $this->load = $load;
    }

    /**
     * @param int $itemsPerPage
     * @return Pagination
     */
    public function setItemsPerPage($itemsPerPage)
    {
        $this->itemsPerPage = $itemsPerPage;
        return $this;
    }

    /**
     * @param int $paginationLinks
     * @return Pagination
     */
    public function setPaginationLinks($paginationLinks)
    {
        $this->paginationLinks = $paginationLinks;
        return $this;
    }

    public function paginate($totalItems, $currentPage, $baseUrl, $pageNumberPfx = '/') {

        $last = ceil($totalItems / $this->itemsPerPage);
        if ($last <= 1) return '';

        $items[]=$this->pushItem($currentPage, $currentPage, $currentPage, "", "active");
        if ($currentPage > 1) $items[]=$this->pushItem(0, $currentPage-1, "<", $baseUrl.( ($currentPage-1 > 1) ? $pageNumberPfx.($currentPage-1) : '') );
        if ($currentPage < $last) $items[]=$this->pushItem(65532, $currentPage+1, ">", $baseUrl.$pageNumberPfx.($currentPage+1));

        if ($currentPage > 1) $items[]=$this->pushItem(1, 1, "1", $baseUrl);

        if ($currentPage > 2) $items[]=$this->pushItem($currentPage-1, $currentPage-1, $currentPage-1, $baseUrl.( ($currentPage-1 > 1) ? $pageNumberPfx.($currentPage-1) : '') );
        if ($currentPage < $last - 1) $items[]=$this->pushItem($currentPage+1, $currentPage+1, $currentPage+1,  $baseUrl.$pageNumberPfx.($currentPage+1));

        // LAST ITEM IS SKIPPED IF THERE ARE NOT ENOUGH SLOTS LEFT
        if ($currentPage < $last && count($items) < $this->paginationLinks) $items[]=$this->pushItem($last, $last, $last, $baseUrl.$pageNumberPfx.$last);

        $free_slots = $this->paginationLinks - count($items);
        $slots_to_assign_right = ceil($free_slots/2);
        $slots_to_assign_left = floor($free_slots/2);

        $right_available_pages = max($last-$currentPage-1 -1, 0);
        $left_available_pages = max($currentPage-1-1 -1, 0);

        $right_slots_not_assigned = $slots_to_assign_right - $right_available_pages;
        $left_slots_not_assigned =  $slots_to_assign_left - $left_available_pages;

        if ( $right_slots_not_assigned > 0 ) {

            $slots_to_assign_right = $right_available_pages;

            if ( $left_slots_not_assigned >= 0) {
                $slots_to_assign_left = $left_available_pages;
            }
            else $slots_to_assign_left += min ( abs($left_slots_not_assigned), $right_slots_not_assigned);
        }

        else if ( $left_slots_not_assigned > 0 ) {

            $slots_to_assign_left = $left_available_pages;

            if ( $right_slots_not_assigned >= 0) {
                $slots_to_assign_right = $right_available_pages;
            }
            else $slots_to_assign_right += min ( abs($right_slots_not_assigned), $left_slots_not_assigned);
        }


        if ($slots_to_assign_left > 0) {
            $step = max (floor(($currentPage - 1 - 1 - 1) / ($slots_to_assign_left + 1)), 1);
            for ($i = 0; $i < $slots_to_assign_left; $i++) {
                $pg = 1 + ($i + 1) * $step;
                $items[] = $this->pushItem($pg, $pg, $pg, $baseUrl.$pageNumberPfx.$pg);
            }
        }

        if ($slots_to_assign_right > 0) {
            $step = max(floor(($last - $currentPage - 1 - 1) / ($slots_to_assign_right +1) ), 1);
            for ($i = 0; $i < $slots_to_assign_right; $i++) {
                $pg = $currentPage + 1 + ($i + 1) * $step;
                $items[] = $this->pushItem($pg, $pg, $pg, $baseUrl.$pageNumberPfx.$pg);
            }
        }

        usort($items, function ($a, $b) {
            return $a['pos'] > $b['pos'];
        });

        return $this->load->view("admin/pagination_v", ["pagination_array" => $items], true);

//	var_dump($items);
    }

    private function pushItem($pos, $page, $caption, $link, $class="") {

        $tmp["pos"] = $pos;
        $tmp["page"] = $page;
        $tmp["capt"] = $caption;
        $tmp["link"] = $link;
        $tmp["class"] = $class;
        $tmp["pfx"] = "";
        $tmp["sfx"] = "";

        return $tmp;
    }

}