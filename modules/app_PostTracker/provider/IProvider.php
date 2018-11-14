<?php
interface IProvider
{
    public function getStatus($track);
    public function addTrack($rec);
    public function delTrack($rec);
    public function archiveTrack($rec);
    public function unarchiveTrack($rec);
}
?>