import m from "mithril";
import Stream from "mithril/stream";

const TOKEN_API_URI = "http://localhost:8080/api/token"

class userModel {
  constructor(data) {
    this.expires = Stream(data.expires);
    this.token = Stream(data.token);
  }
}

class User {
  login(user_name, password) {
    return m.request({
      method: "POST",
      url: TOKEN_API_URI,
      user: user_name,
      password: password,
      type: userModel
    })
    .then((result) => {
      localStorage.setItem('token', result.token());
    })
    .catch((e) => {
      throw new Error(e);
    });
  }

  refreshToken() {
    return m.request({
      method: "POST",
      url: TOKEN_API_URI + "_refresh",
      type: userModel,
      headers: {
        "Authorization": "Bearer " + this.getToken()
      }
    })
    .then((result) => {
      localStorage.setItem('token', result.token());
    })
    .catch((e) => {
      throw new Error(e);
    });
  }

  getToken() {
    return localStorage.getItem('token');
  }
}

export default new User();
