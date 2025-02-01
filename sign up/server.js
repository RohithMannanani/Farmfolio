const express = require('express');
const fetch = require('node-fetch');
const app = express();
const port = 3000;

app.use(express.json());

// Proxy route for fetching states
app.get('/api/states', async (req, res) => {
  try {
    const response = await fetch('https://cdn-api.co-vin.in/api/v2/admin/location/states');
    const data = await response.json();
    res.json(data);
  } catch (error) {
    console.error('Error fetching states:', error);
    res.status(500).send('Error fetching states');
  }
});

// Proxy route for fetching districts based on stateId
app.get('/api/districts/:stateId', async (req, res) => {
  const { stateId } = req.params;
  try {
    const response = await fetch(`https://cdn-api.co-vin.in/api/v2/admin/location/districts/${stateId}`);
    const data = await response.json();
    res.json(data);
  } catch (error) {
    console.error('Error fetching districts:', error);
    res.status(500).send('Error fetching districts');
  }
});

app.listen(port, () => {
  console.log(`Server running on http://localhost:${port}`);
});
