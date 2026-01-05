package com.hierarchychat.models

import com.google.gson.annotations.SerializedName

data class ApiResponse<T>(
    val success: Boolean,
    val message: String?,
    val data: T?
)

data class AuthResponse(
    val user: User,
    val token: String
)

data class User(
    val id: Long,
    val name: String,
    val email: String,
    val role: String,
    @SerializedName("is_active") val isActive: Boolean = true
)

data class UserBrief(
    val id: Long,
    val name: String,
    val role: String
)

data class Conversation(
    val user: UserBrief,
    @SerializedName("last_message") val lastMessage: MessageBrief,
    @SerializedName("unread_count") val unreadCount: Int = 0
)

data class MessageBrief(
    val content: String,
    @SerializedName("created_at") val createdAt: String,
    @SerializedName("is_from_me") val isFromMe: Boolean = false
)

data class Message(
    val id: Long,
    val content: String,
    val sender: UserBrief?,
    @SerializedName("is_from_me") val isFromMe: Boolean = false,
    @SerializedName("created_at") val createdAt: String
)

data class LoginRequest(
    val email: String,
    val password: String
)

data class SendMessageRequest(
    val content: String
)
