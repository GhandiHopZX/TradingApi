import React from 'react';
import ReactDOM from 'react-dom';
import './index.css';
import App from './App';
import registerServiceWorker from './registerServiceWorker';

ReactDOM.render(<App />, document.getElementById('root'));
registerServiceWorker();

const express = require('express');

const api = express();
api.listen(3000, () => {
    console.log('API active');
});

api.get('/', (req, res) => {
    console.log(req);
    res.send('hello world');
});