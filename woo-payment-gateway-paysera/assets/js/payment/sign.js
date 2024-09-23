let wtpQualitySign_projectId = data.project_id;
let wtpQualitySign_language = data.language;

document.write(
    '<script src="' +
    'https://www.paysera.com/new/' +
    wtpQualitySign_language +
    '/projects/get_quality_sign_code/' +
    wtpQualitySign_projectId +
    '"></script>'
);
