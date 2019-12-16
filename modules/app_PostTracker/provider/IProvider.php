<?php
interface IProvider
{
    public function getStatus($track);
    public function getList();
    public function addTrack($rec);
    public function delTrack($rec);
    public function archiveTrack($rec);
    public function unarchiveTrack($rec);
}
?>