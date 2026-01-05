package com.hierarchychat.utils

import android.content.Context
import android.content.SharedPreferences
import com.google.gson.Gson
import com.hierarchychat.models.User

object SessionManager {
    
    private const val PREF_NAME = "hierarchy_chat_prefs"
    private const val KEY_TOKEN = "auth_token"
    private const val KEY_USER = "user_data"
    
    private lateinit var prefs: SharedPreferences
    private val gson = Gson()
    
    fun init(context: Context) {
        prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE)
    }
    
    fun saveToken(token: String) {
        prefs.edit().putString(KEY_TOKEN, token).apply()
    }
    
    fun getToken(): String? = prefs.getString(KEY_TOKEN, null)
    
    fun saveUser(user: User) {
        prefs.edit().putString(KEY_USER, gson.toJson(user)).apply()
    }
    
    fun getUser(): User? {
        val json = prefs.getString(KEY_USER, null) ?: return null
        return try { gson.fromJson(json, User::class.java) } catch (e: Exception) { null }
    }
    
    fun getUserId(): Long = getUser()?.id ?: 0
    
    fun isLoggedIn(): Boolean = getToken() != null
    
    fun logout() {
        prefs.edit().clear().apply()
    }
}
