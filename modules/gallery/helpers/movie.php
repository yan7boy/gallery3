<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2009 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * This is the API for handling movies.
 *
 * Note: by design, this class does not do any permission checking.
 */
class movie_Core {
  static function get_edit_form($movie) {
    $form = new Forge("movies/update/$movie->id", "", "post", array("id" => "g-edit-movie-form"));
    $form->hidden("from_id");
    $group = $form->group("edit_item")->label(t("Edit Movie"));
    $group->input("title")->label(t("Title"))->value($movie->title);
    $group->textarea("description")->label(t("Description"))->value($movie->description);
    $group->input("name")->label(t("Filename"))->value($movie->name)
      ->error_messages(
        "conflict", t("There is already a movie, photo or album with this name"))
      ->error_messages("no_slashes", t("The movie name can't contain a \"/\""))
      ->error_messages("no_trailing_period", t("The movie name can't end in \".\""))
      ->error_messages("illegal_extension", t("You cannot change the filename extension"));
    $group->input("slug")->label(t("Internet Address"))->value($movie->slug)
      ->error_messages(
        "conflict", t("There is already a movie, photo or album with this internet address"))
      ->error_messages(
        "not_url_safe",
        t("The internet address should contain only letters, numbers, hyphens and underscores"));

    module::event("item_edit_form", $movie, $form);

    $group = $form->group("buttons")->label("");
    $group->submit("")->value(t("Modify"));

    return $form;
  }


  static function getmoviesize($filename) {
    $ffmpeg = self::find_ffmpeg();
    if (empty($ffmpeg)) {
      throw new Exception("@todo MISSING_FFMPEG");
    }

    $cmd = escapeshellcmd($ffmpeg) . " -i " . escapeshellarg($filename) . " 2>&1";
    $result = `$cmd`;
    if (preg_match("/Stream.*?Video:.*?(\d+)x(\d+)/", $result, $regs)) {
      list ($width, $height) = array($regs[1], $regs[2]);
    } else {
      list ($width, $height) = array(0, 0);
    }
    return array($width, $height);
  }

  static function extract_frame($input_file, $output_file) {
    $ffmpeg = self::find_ffmpeg();
    if (empty($ffmpeg)) {
      throw new Exception("@todo MISSING_FFMPEG");
    }

    $cmd = escapeshellcmd($ffmpeg) . " -i " . escapeshellarg($input_file) .
      " -an -ss 00:00:03 -an -r 1 -vframes 1" .
      " -y -f mjpeg " . escapeshellarg($output_file) . " 2>&1";
    exec($cmd);
  }

  static function find_ffmpeg() {
    if (!$ffmpeg_path = module::get_var("gallery", "ffmpeg_path")) {
      $graphics_path = module::get_var("gallery", "graphics_toolkit_path", null);

      putenv("PATH=" . getenv("PATH") . (empty($graphics_path) ? "" : ":$graphics_path") .
             ":/usr/local/bin:/opt/local/bin:/opt/bin");
      if (function_exists("exec")) {
        $ffmpeg_path = exec("which ffmpeg");
      }

      module::set_var("gallery", "ffmpeg_path", $ffmpeg_path);
    }
    return $ffmpeg_path;
  }
}
