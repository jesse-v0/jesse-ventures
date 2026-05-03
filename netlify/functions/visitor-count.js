// ============================================================
// jesse.ventures — Visitor Counter
// Netlify Function (Node.js) — Supabase backend
// TODO: wire up Supabase client
// ============================================================

exports.handler = async function (event, context) {
  return {
    statusCode: 200,
    body: JSON.stringify({ count: 0, message: 'Visitor counter — coming soon' }),
  };
};
