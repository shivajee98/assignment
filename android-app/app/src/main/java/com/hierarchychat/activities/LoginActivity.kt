package com.hierarchychat.activities

import android.content.Intent
import android.os.Bundle
import android.view.View
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.hierarchychat.api.ApiClient
import com.hierarchychat.databinding.ActivityLoginBinding
import com.hierarchychat.models.LoginRequest
import com.hierarchychat.utils.SessionManager
import com.hierarchychat.utils.Utils
import kotlinx.coroutines.launch

class LoginActivity : AppCompatActivity() {
    
    private lateinit var binding: ActivityLoginBinding
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)
        
        binding.btnLogin.setOnClickListener {
            performLogin()
        }
    }
    
    private fun performLogin() {
        val email = binding.etEmail.text.toString().trim()
        val password = binding.etPassword.text.toString()
        
        if (email.isEmpty()) {
            binding.tilEmail.error = "Email is required"
            return
        }
        
        if (password.isEmpty()) {
            binding.tilPassword.error = "Password is required"
            return
        }
        
        binding.tilEmail.error = null
        binding.tilPassword.error = null
        showLoading(true)
        
        lifecycleScope.launch {
            try {
                val request = LoginRequest(email, password)
                val response = ApiClient.getApiService().login(request)
                
                if (response.isSuccessful && response.body()?.success == true) {
                    val data = response.body()?.data
                    if (data != null) {
                        SessionManager.saveToken(data.token)
                        SessionManager.saveUser(data.user)
                        ApiClient.resetClient()
                        
                        startActivity(Intent(this@LoginActivity, MainActivity::class.java))
                        finish()
                    }
                } else {
                    Utils.showToast(this@LoginActivity, response.body()?.message ?: "Login failed")
                }
            } catch (e: Exception) {
                Utils.showToast(this@LoginActivity, "Error: ${e.message}")
            } finally {
                showLoading(false)
            }
        }
    }
    
    private fun showLoading(show: Boolean) {
        binding.progressBar.visibility = if (show) View.VISIBLE else View.GONE
        binding.btnLogin.isEnabled = !show
    }
}
