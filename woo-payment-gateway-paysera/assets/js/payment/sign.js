(function() {
    'use strict';

    if (typeof payseraQualitySign === 'undefined') {
        console.error('Paysera Quality Sign data is not defined');
        return;
    }

    const { project_id, language, script_url } = payseraQualitySign;

    if (!project_id || !language || !script_url) {
        console.error('Missing Paysera Quality Sign data');
        return;
    }

    const versionDate = new Date().toISOString().split('T')[0];
    const script = Object.assign(document.createElement('script'), {
        src: `${script_url}?v=${versionDate}`,
        async: true,
        id: 'paysera-quality-sign-script',
        type: 'text/javascript'
    });

    script.id = "paysera-quality-sign-script";
    script.type = "text/javascript";

    script.setAttribute('data-paysera-project-id', project_id);
    script.setAttribute('data-lang', language);

    (document.head || document.body).appendChild(script);
})();
