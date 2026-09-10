{{-- Century Gothic registration for dompdf.

     The AFS design system is built on Century Gothic (see
     resources/css/app.css). dompdf cannot resolve system font
     names, so the face is registered here from the TTFs bundled
     in public/fonts/century-gothic/.

     Paths are absolute and resolved by public_path(). dompdf's
     chroot is realpath(base_path()), so these sit inside it and
     load without enable_remote. On first render dompdf compiles
     the fonts into storage/fonts/ — that directory must exist
     and be writable.

     The archive supplies Regular and Bold only. Italic is left
     undeclared on purpose: dompdf does not synthesise oblique,
     so mapping italic to the upright file would silently drop
     the distinction that .afs-empty and .afs-continued rely on.
     Those fall through to the next family in the stack. --}}
<style>
    @font-face {
        font-family: "Century Gothic";
        font-style: normal;
        font-weight: normal;
        src: url("{{ public_path('fonts/century-gothic/CenturyGothic-Regular.ttf') }}") format("truetype");
    }
    @font-face {
        font-family: "Century Gothic";
        font-style: normal;
        font-weight: bold;
        src: url("{{ public_path('fonts/century-gothic/CenturyGothic-Bold.ttf') }}") format("truetype");
    }
</style>
