/**
 * Generate avatar URL based on name using UI Avatars API
 * @param {string} name - The name to generate avatar for
 * @param {object} options - Additional options
 * @returns {string} - Avatar URL
 */
export const generateAvatar = (name, options = {}) => {
    if (!name) name = 'User';

    const defaults = {
        size: 128,
        background: 'random', // or specific color like '667eea'
        color: 'fff',
        bold: true,
        rounded: false
    };

    const settings = { ...defaults, ...options };

    // UI Avatars API
    const params = new URLSearchParams({
        name: name,
        size: settings.size,
        background: settings.background,
        color: settings.color,
        bold: settings.bold,
        rounded: settings.rounded
    });

    return `https://ui-avatars.com/api/?${params.toString()}`;
};

/**
 * Alternative: DiceBear Avatars (more styles available)
 */
export const generateDiceBearAvatar = (name, style = 'initials') => {
    if (!name) name = 'User';

    // Available styles: adventurer, avataaars, bottts, identicon, initials, etc.
    return `https://api.dicebear.com/7.x/${style}/svg?seed=${encodeURIComponent(name)}`;
};