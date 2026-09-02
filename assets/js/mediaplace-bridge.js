/**
 * Gemeinsame Weiche zwischen dem klassischen Medienpool-Popup und dem
 * MediaPlace-Overlay (FriendsOfREDAXO/mediaplace), falls installiert und
 * aktiv. Wird von content-builder.js (BeMediaField) und field-widgets.js
 * (SmartLinkField) genutzt -- an einer Stelle definiert statt pro Widget
 * dupliziert, siehe boot.php (laedt diese Datei vor den beiden genannten).
 * Guard (window.X = window.X || {...}) sorgt dafuer, dass eine bereits von
 * einem anderen AddOn (z.B. mform, tinymce, cke5) definierte Bridge nicht
 * ueberschrieben wird -- keine harte Abhaengigkeit von MediaPlace in
 * irgendeine Richtung, gleiches Muster wie mform/assets/js/mediaplace-bridge.js.
 */
window.rex5MediaplaceBridge = window.rex5MediaplaceBridge || {
    isActive: function () {
        return typeof MP !== 'undefined' && typeof MP.open === 'function';
    },
    // onSelect(filename) wie beim klassischen Popup (Single-Select).
    // onSelect(filenames[]) bei options.multiple (Array von Dateinamen).
    // options.filter waehlt optional den Start-Typ-Tab vor (z.B. 'images').
    pick: function (onSelect, options) {
        MP.open(onSelect, options || {});
    },
    // Oeffnet den Overlay direkt im Detail-Panel einer Datei (Browse-only).
    show: function (filename) {
        if (typeof MP.openFile === 'function') {
            MP.openFile(filename);
        }
    }
};

/**
 * Best-effort-Ableitung eines MediaPlace-Start-Tabs (options.filter) aus
 * einer Endungsliste (builder's fieldConfig['allowed_types']). Nur eine
 * Rueckmeldung, wenn die Liste eindeutig EINER Kategorie zuzuordnen ist --
 * bei gemischten oder unbekannten Endungen lieber gar keinen Filter setzen
 * (Overlay startet bei "Alle Medien") als falsch zu raten. filter ist in
 * MediaPlace ohnehin nur ein Startwert, die eigentliche Einschraenkung
 * uebernimmt options.allowedExtensions.
 */
function cbMediaplaceFilterForTypes(rawTypes) {
    var IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'bmp', 'tif', 'tiff'];
    var VIDEO_EXT = ['mp4', 'webm', 'ogv', 'mov', 'm4v'];
    var AUDIO_EXT = ['mp3', 'wav', 'flac', 'aac', 'm4a'];
    var DOC_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];

    var types = Array.isArray(rawTypes) ? rawTypes : String(rawTypes || '').split(',');
    types = types.map(function (t) {
        return String(t || '').trim().toLowerCase();
    }).filter(Boolean);
    if (!types.length) return null;

    var buckets = { images: 0, videos: 0, audio: 0, documents: 0, other: 0 };
    types.forEach(function (ext) {
        if (IMAGE_EXT.indexOf(ext) !== -1) buckets.images++;
        else if (VIDEO_EXT.indexOf(ext) !== -1) buckets.videos++;
        else if (AUDIO_EXT.indexOf(ext) !== -1) buckets.audio++;
        else if (DOC_EXT.indexOf(ext) !== -1) buckets.documents++;
        else buckets.other++;
    });

    var nonZero = Object.keys(buckets).filter(function (key) {
        return buckets[key] > 0;
    });
    return 1 === nonZero.length ? nonZero[0] : null;
}

/**
 * Liest eine kommagetrennte Endungsliste aus einem data-cb-media-types-Attribut.
 */
function cbMediaplaceExtensionsFromAttr(raw) {
    return String(raw || '').split(',').map(function (t) {
        return String(t || '').trim().toLowerCase();
    }).filter(Boolean);
}
