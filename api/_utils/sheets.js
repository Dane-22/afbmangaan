// Webhook-based data layer - uses make.com to interact with Google Sheets
// No direct Google Sheets API needed

const MAKE_WEBHOOK_URL = process.env.MAKE_WEBHOOK_URL;

// Helper: Call make.com webhook
async function callWebhook(entity, action, data, importType = 'single') {
  if (!MAKE_WEBHOOK_URL) {
    throw new Error('MAKE_WEBHOOK_URL not configured');
  }

  const payload = {
    entity,
    action,
    import_type: importType,
    data
  };

  const response = await fetch(MAKE_WEBHOOK_URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload)
  });

  if (!response.ok) {
    throw new Error(`Webhook failed: ${response.status} ${response.statusText}`);
  }

  return await response.json();
}

// Fetch data from Google Sheets via webhook query
async function getSheetData(sheetName) {
  try {
    // Try to fetch from webhook first (if Make.com supports query operations)
    const result = await callWebhook(
      sheetName.toLowerCase().replace('s', ''),
      'get_all',
      {}
    );
    
    if (result && result.data) {
      return result.data;
    }
    
    // Fallback: return empty array if webhook doesn't support reads
    console.warn(`Webhook query not supported for ${sheetName}, returning empty array`);
    return [];
  } catch (error) {
    console.error(`Error fetching ${sheetName}:`, error);
    // Return empty array on error to prevent crashes
    return [];
  }
}

// Helper: Append row via webhook
async function appendRow(sheetName, values) {
  const entity = sheetName.toLowerCase().replace('s', '');
  const data = Object.fromEntries(values);
  return await callWebhook(entity, 'create', data);
}

// Helper: Update row via webhook
async function updateRow(sheetName, rowIndex, values) {
  const entity = sheetName.toLowerCase().replace('s', '');
  const data = { ...Object.fromEntries(values), rowIndex };
  return await callWebhook(entity, 'update', data);
}

// Helper: Generate next ID
async function getNextId(sheetName) {
  const data = await getSheetData(sheetName);
  if (data.length === 0) return 1;
  const maxId = Math.max(...data.map(row => parseInt(row.id) || 0));
  return maxId + 1;
}

module.exports = {
  MAKE_WEBHOOK_URL,
  callWebhook,
  getSheetData,
  appendRow,
  updateRow,
  getNextId
};
