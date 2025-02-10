<?php

namespace Alexplusde\Urlaub;


class Urlaub
{

    public static function getAllArticleIds() :array {
        return [];
    }

    public static function getRewriteUrl(\rex_extension_point $ep) {
        return;
    }
    // https://github.com/yakamara/redaxo_yrewrite/blob/64ecb56e843c7752465dca20939b01ecc19c90f4/lib/yrewrite/seo.php#L328-L330

    public static function addUrlToYrewriteSitemap(string $url) {
        $sitemap_entry =
        "\n".'<url>'.
        "\n\t".'<loc>'.rex_yrewrite::getFullPath($path[$clang_id]).'</loc>'.
        "\n\t".'<lastmod>'.date(DATE_W3C, $article->getUpdateDate()).'</lastmod>'; // Serverzeitzone passt
      if ($article->getValue(self::$meta_image_field)) {
          $media = rex_media::get((string) $article->getValue(self::$meta_image_field));
          $sitemap_entry .= "\n\t".'<image:image>'.
              "\n\t\t".'<image:loc>'.rtrim(rex_yrewrite::getDomainByArticleId($article->getId())->getUrl(), '/').rex_media_manager::getUrl('yrewrite_seo_image', $media->getFileName()).'</image:loc>'.
              ($media->getTitle() ? "\n\t\t".'<image:title>'.rex_escape($media->getTitle()).'</image:title>' : '').
              "\n\t".'</image:image>';
      }
      $sitemap_entry .= "\n\t".'<changefreq>'.$changefreq.'</changefreq>'.
        "\n\t".'<priority>'.$priority.'</priority>'.
        "\n".'</url>';
      $sitemap[] = $sitemap_entry;
    }

}
