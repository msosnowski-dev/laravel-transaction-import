/**
 * API client for bank transactions import system.
 */

export async function fetchImports(page = 1) {
    const response = await fetch(`/api/imports?page=${page}`, {
        headers: {
            'Accept': 'application/json',
        },
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message);
    }

    return await response.json();
}

export async function fetchImportDetails(id) {
    const response = await fetch(`/api/imports/${id}`, {
        headers: {
            'Accept': 'application/json',
        },
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message);
    }

    return await response.json();
}

export async function uploadImportFile(file) {
    const formData = new FormData();
    formData.append('file', file);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const headers = {
        'Accept': 'application/json',
    };
    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    const response = await fetch('/api/imports', {
        method: 'POST',
        headers,
        body: formData,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        let message = data.message;
        if (data.errors && data.errors.file) {
            message = data.errors.file.join(' ');
        }
        const error = new Error(message);
        error.errors = data.errors;
        throw error;
    }

    return data;
}
