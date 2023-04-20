import React, { useState } from 'react';
import { useGoogleLogin } from '@react-oauth/google';

function App() {
    const [ tokens, setTokens ] = useState();

    const login = useGoogleLogin({
        onSuccess: codeResponse => getAccessToken(codeResponse),
    });

    async function getAccessToken(user) {
        const response = await fetch('http://localhost:8000/api/auth/google', {
                method: 'post',
                headers: {
                    'Accept': 'application/json, text/plain, */*',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({token: user.access_token})
            });
        const jsonData = await response.json();

        setTokens(jsonData);
    }

    return (
        <div>
            <h2>React Google Login</h2>
            <br />
            <br />
            { tokens ? (
                <div>
                    <h3>User Logged in</h3>
                    <p>Access Token: {tokens.token}</p>
                    <p>Refresh Token: {tokens.refresh_token}</p>
                    <br />
                    <br />
                </div>
            ) : (
                <button onClick={() => login()}>Sign in with Google 🚀 </button>
            )}
        </div>
    );
}
export default App;